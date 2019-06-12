<?php
namespace App\Service;

use App\Entity\CommonLog;
use App\Entity\MasterData\Commodity;
use App\Entity\MasterData\Currency;
use App\Entity\MasterData\UOM;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ImportManager
{
    private $em;
    private $tokenStorage;
    private $logManager;

    /**
     * TradeManager constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param LogManager $logManager
     */
    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, LogManager $logManager)
    {
        $this->em = $em;

        $this->tokenStorage = $tokenStorage;

        $this->logManager = $logManager;
    }

    /**
     * @param string $class
     * @param array $colsMapping
     * @param string $identifierIndex
     * @param string $values
     * @return void
     */
    public function import(string $class, array $colsMapping, string $identifierIndex, string $values)
    {
        $valuesAsArray = $this->parseXMLValues($values);

        $entityRepository = $this->em->getRepository($class);

        foreach ($valuesAsArray as $values) {
            $flush = false;

            $entity = $entityRepository->findOneBy([$colsMapping[$identifierIndex] => $values[$identifierIndex]]);

            if (!$entity instanceof $class) {
                $entity = new $class();
            }

            $persist = true;

            foreach ($colsMapping as $k => $col) {
                if (!empty($values[$k])) {
                    $function = 'set' . ucfirst($col);

                    switch ($col) {
                        case UOM::class:
                            $uom = $this->em->getRepository(UOM::class)->findOneByCode($values[$k]);

                            if (!$uom instanceof UOM) {
                                $persist = false;
                                continue;
                            } else {
                                $function = 'set' . ucfirst(strtolower(explode('\\', $col)[count(explode('\\', $col)) - 1]));
                                $entity->$function($uom);
                            }
                            break;

                        case Currency::class:
                            if ($persist) {
                                $currency = $this->em->getRepository(Currency::class)->findOneBy(['uom' => $uom, 'code' => $values[$k]]);

                                if (!$currency instanceof Currency) {
                                    $entityData = [
                                        'code' => $values[$k],
                                        'name' => $values[$k],
                                        'uom' => $uom,
                                        'active' => true,
                                    ];
                                    $currency = $this->createNewEntity(Currency::class, $entityData);
                                }

                                $entity->setCurrency($currency);
                            }
                            break;

                        case Commodity::class:
                            $commodity = $this->em->getRepository($col)->findOneByName($values[$k]);

                            if (!$commodity instanceof Commodity) {
                                $entityData = [
                                    'name' => $values[$k],
                                ];
                                $commodity = $this->createNewEntity(Commodity::class, $entityData);
                                $flush = true;
                            }

                            $entity->setCommodity($commodity);
                            break;

                        case 'updatedAt':
                            $date = new \DateTime($values[$k]);
                            $entity->setUpdatedAt($date);
                            break;

                        default:
                            $entity->$function($values[$k]);
                            break;
                    }
                } else {
                    $persist = false;
                }
            }

            if ($persist) {
                $entity->setActive(true);
                $this->em->persist($entity);

                if ($flush) {
                    $this->em->flush();
                }
            }
        }

        $this->em->flush();
    }

    /**
     * @param string $values
     * @param int $nbCols
     * @return array
     */
    public function parseTPTValues(string $values, int $nbCols): array
    {
        $valuesInfosAsArray = explode('|', $values);
        $index = 0;

        for ($i = 0; $i < 5; $i++) {
            unset($valuesInfosAsArray[$i]);
        }

        $valuesInfosAsArray = array_values($valuesInfosAsArray);
        $valuesAsArray = [];

        foreach ($valuesInfosAsArray as $k => $valueInfo) {
            if ($k % $nbCols == 0) {
                $index++;
            }

            $valuesAsArray[$index][] = $valueInfo;
        }

        $filteredValuesAsArray = [];

        foreach($valuesAsArray as $values) {
            if (count($values) != $nbCols) {
                $this->logManager->createLog(null, null, CommonLog::TYPE_IMPORT_MISSING_VALUES);
                continue;
            }

            $filteredValuesAsArray[] = $values;
        }

        return $filteredValuesAsArray;
    }

    /**
     * Get the array value of the API response
     *
     * @param string $values api xml string response
     *
     * @return array
     */
    public function parseXMLValues(string $values)
    {
        $xml_decoded = html_entity_decode($values);
        $xml_decoded = str_replace('<?xml version="1.0"?>', '', $xml_decoded);

        $xml = new \SimpleXMLElement($xml_decoded);
        $body = $xml->xpath('//Entity');
        $entities = json_decode(json_encode((array)$body), TRUE);

        $entitiesValues = [];
        foreach ($entities as $key => $entity) {
            foreach ($entity['Property'] as $index => $property) {
                $entitiesValues[$key][$property['@attributes']['name']] = $property['@attributes']['value'];
            }
        }

        return $entitiesValues;
    }

    /**
     * @param string $class
     * @param array $entityData
     *
     * @return Currency|Commodity|null
     */
    private function createNewEntity(string $class, array $entityData)
    {
        if (class_exists($class)) {
            $entity = new $class();

            foreach ($entityData as $attr => $datum) {
                $setter = 'set' . $attr;
                $entity->$setter($datum);
            }

            $this->em->persist($entity);

            return $entity;
        } else {
            return null;
        }
    }
}
