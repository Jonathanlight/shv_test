<?php

namespace App\Service;

use App\Entity\MasterData\Commodity;
use App\Entity\MasterData\ConversionTable;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\UOM;
use App\Entity\RMP;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class UomConverterManager
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param $value
     * @param Commodity $commodity
     * @param UOM $uomFrom
     * @param UOM $uomTo
     * @return string
     */
    public function convert($value, Commodity $commodity, UOM $uomFrom, UOM $uomTo)
    {
        $fromConversionTable = $this->em->getRepository(ConversionTable::class)->findOneBy(['commodity' => $commodity, 'uom' => $uomFrom]);
        $toConversionTable = $this->em->getRepository(ConversionTable::class)->findOneBy(['commodity' => $commodity, 'uom' => $uomTo]);

        $valueTo = 0;

        if ($fromConversionTable) {
            $valueInMt = bcmul($value, str_replace(',', '.', $fromConversionTable->getValue()), 20);
            $valueTo = bcdiv($valueInMt, str_replace(',', '.', $toConversionTable->getValue()), 20);
        }

        return $valueTo;
    }
}
