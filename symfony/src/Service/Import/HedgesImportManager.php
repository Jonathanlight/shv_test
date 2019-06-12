<?php
namespace App\Service\Import;

use App\Constant\Instruments;
use App\Constant\Operations;
use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\HedgeLog;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\Currency;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\Strategy;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\Trade;
use App\Entity\User;
use App\Service\HedgeVolumeManager;
use App\Service\LogManager;
use App\Service\RmpManager;
use App\Service\RmpSubSegmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\Functional\TestBundle\Entity\Prod;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class HedgesImportManager
{
    const IMPORT_HEADER_REF2 = 0;
    const IMPORT_HEADER_COUNTERPART_COMPANY = 1;
    const IMPORT_HEADER_PRODUCT_1 = 2;
    const IMPORT_HEADER_PRODUCT_2 = 3;
    const IMPORT_HEADER_CURRENCY = 4;
    const IMPORT_HEADER_UOM = 5;
    const IMPORT_HEADER_VENTURE = 6;
    const IMPORT_HEADER_PRICE_RISK_CLASSIFICATION = 7;
    const IMPORT_HEADER_OPERATION_TYPE = 8;
    const IMPORT_HEADER_SUB_SEGMENT = 9;
    const IMPORT_HEADER_STRATEGY = 10;
    const IMPORT_HEADER_QUANTITY = 11;
    const IMPORT_HEADER_START_DATE = 12;
    const IMPORT_HEADER_END_DATE = 13;
    const IMPORT_HEADER_WAIVERS = 14;
    const IMPORT_HEADER_WAIVER_CLASS_RISK = 15;
    const IMPORT_HEADER_HEDGING_TOOL_CLASS_RISK = 16;
    const IMPORT_HEADER_PROTECTION_PRICE = 17;
    const IMPORT_HEADER_MAX_LOSS = 18;
    const IMPORT_HEADER_PREMIUM_HEDGING_TOOL = 19;
    const IMPORT_HEADER_PUT_CALL = 20;
    const IMPORT_HEADER_STRIKE = 21;
    const IMPORT_HEADER_FIXED_PRICE = 22;
    const IMPORT_HEADER_CXL_TRADE_NUMBER = 23;
    const IMPORT_HEADER_TRADING_DATE = 24;
    const IMPORT_HEADER_HEDGE_REALIZED = 25;

    public static $importHeaders = [
        self::IMPORT_HEADER_REF2 => 'ref2',
        self::IMPORT_HEADER_COUNTERPART_COMPANY => 'counterpartCompany',
        self::IMPORT_HEADER_PRODUCT_1 => 'product1Code',
        self::IMPORT_HEADER_PRODUCT_2 => 'product2Code',
        self::IMPORT_HEADER_CURRENCY => 'currencyCode',
        self::IMPORT_HEADER_UOM => 'uomCode',
        self::IMPORT_HEADER_VENTURE => 'ventureCd',
        self::IMPORT_HEADER_PRICE_RISK_CLASSIFICATION => 'priceRiskClassification',
        self::IMPORT_HEADER_OPERATION_TYPE => 'operationType',
        self::IMPORT_HEADER_SUB_SEGMENT => 'subSegment',
        self::IMPORT_HEADER_STRATEGY => 'strategy',
        self::IMPORT_HEADER_QUANTITY => 'quantity',
        self::IMPORT_HEADER_START_DATE => 'startDate',
        self::IMPORT_HEADER_END_DATE => 'endDate',
        self::IMPORT_HEADER_WAIVERS => 'waivers',
        self::IMPORT_HEADER_WAIVER_CLASS_RISK => 'waiverClassRisk',
        self::IMPORT_HEADER_HEDGING_TOOL_CLASS_RISK => 'hedgingToolClassRisk',
        self::IMPORT_HEADER_PROTECTION_PRICE => 'protectionPrice',
        self::IMPORT_HEADER_MAX_LOSS => 'maxLoss',
        self::IMPORT_HEADER_PREMIUM_HEDGING_TOOL => 'premiumHedgingTool',
        self::IMPORT_HEADER_PUT_CALL => 'putCall',
        self::IMPORT_HEADER_STRIKE => 'strike',
        self::IMPORT_HEADER_FIXED_PRICE => 'fixedPrice',
        self::IMPORT_HEADER_CXL_TRADE_NUMBER => 'cxlTradeNumber',
        self::IMPORT_HEADER_TRADING_DATE => 'tradingDate',
        self::IMPORT_HEADER_HEDGE_REALIZED => 'hedgeRealized'
    ];

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RmpManager
     */
    private $rmpManager;

    /**
     * @var RmpSubSegmentManager
     */
    private $rmpSubSegmentManager;

    /**
     * @var LogManager
     */
    private $logManager;

    /**
     * @var HedgeVolumeManager
     */
    private $hedgeVolumeManager;

    /**
     * TradeManager constructor.
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     * @param RmpManager $rmpManager
     * @param RmpSubSegmentManager $rmpSubSegmentManager
     * @param LogManager $logManager
     * @param HedgeVolumeManager $hedgeVolumeManager
     */
    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage, TranslatorInterface $translator,
                                RmpManager $rmpManager, RmpSubSegmentManager $rmpSubSegmentManager, LogManager $logManager, HedgeVolumeManager $hedgeVolumeManager)
    {
        $this->em = $em;

        $this->tokenStorage = $tokenStorage;

        $this->translator = $translator;

        $this->rmpManager = $rmpManager;

        $this->rmpSubSegmentManager = $rmpSubSegmentManager;

        $this->logManager = $logManager;

        $this->hedgeVolumeManager = $hedgeVolumeManager;
    }

    public function importData(string $fullPath)
    {
        $errors = [];
        $content = fopen($fullPath, 'r');
        $header = fgetcsv($content, 2048, ',');

        foreach ($header as $colNumber => $col) {
            if ($col != self::$importHeaders[$colNumber]) {
                $errors[0][] = 'Error: Bad header column ' . ($colNumber + 1);
            }
        }

        $data = fgetcsv($content, 0, ',', '"', '$');
        $lineNumber = 1;

        $rmpSubSegments = [];

        if (!count($errors)) {
            while (false !== $data) {
                $lineErrors = [];

                $maturityDate = new \DateTime($data[self::IMPORT_HEADER_START_DATE]);
                $year = $maturityDate->format('Y');

                // Check format
                if (!preg_match('/\d+/', $data[self::IMPORT_HEADER_REF2])) {
                    $lineErrors[] = $this->translator->trans('import.hedges.bad_format', ['%lineNumber%' => $lineNumber, '%element%' => 'ref2']);
                }

                $operationType = ucfirst(strtolower($data[self::IMPORT_HEADER_OPERATION_TYPE]));
                if (!in_array($operationType, Operations::$operationTypeForImportInverted)) {
                    $lineErrors[] = $this->translator->trans('import.hedges.bad_format', ['%lineNumber%' => $lineNumber, '%element%' => 'operationType']);
                }

                $floatInputs = ['strike' => $data[self::IMPORT_HEADER_STRIKE], 'fixedPrice' => $data[self::IMPORT_HEADER_FIXED_PRICE]];
                
                foreach ($floatInputs as $colName => $value) {
                    if (!preg_match('/^[-+]?\d*\,?\d*$/', $value)) {
                        $lineErrors[] = $this->translator->trans('import.hedges.bad_format', ['%lineNumber%' => $lineNumber, '%element%' => $colName]);
                    }
                }

                // Check if all required entities exist
                $creator = $this->em->getRepository(User::class)->findOneBy(['firstName' => User::DEFAULT_USER_FIRSTNAME, 'lastName' => User::DEFAULT_USER_LASTNAME]);

                if (!$creator instanceof User) {
                    $lineErrors[0] = 'Error: please create default CYLIPOL user';
                }

                $subSegment = $this->em->getRepository(SubSegment::class)->findOneByName($data[self::IMPORT_HEADER_SUB_SEGMENT]);

                if (!$subSegment instanceof SubSegment) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Sub segment']);
                }

                $businessUnit = $this->em->getRepository(BusinessUnit::class)->findOneByCounterpartCode($data[self::IMPORT_HEADER_COUNTERPART_COMPANY]);

                if (!$businessUnit instanceof BusinessUnit) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Business unit']);
                }

                $rmp = $this->em->getRepository(RMP::class)->findOneBy(['validityPeriod' => $year, 'businessUnit' => $businessUnit, 'status' => RMP::STATUS_APPROVED]);

                if (!$rmp instanceof RMP) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'RMP']);
                }

                $maturity = $this->em->getRepository(Maturity::class)->findOneByDate($maturityDate);

                if (!$maturity instanceof Maturity) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Maturity']);
                }

                $productRepository = $this->em->getRepository(Product::class);
                $product1 = $productRepository->findOneByCode($data[self::IMPORT_HEADER_PRODUCT_1]);

                if (!$product1 instanceof Product) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Product 1']);
                }

                $product2 = $productRepository->findOneByCode($data[self::IMPORT_HEADER_PRODUCT_2]);

                if (!$product2 instanceof Product && !empty($data[self::IMPORT_HEADER_PRODUCT_2])) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Product 2']);
                }

                $currency = $this->em->getRepository(Currency::class)->findOneByCode($data[self::IMPORT_HEADER_CURRENCY]);

                if (!$currency instanceof Currency) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Currency']);
                }

                $uom = $this->em->getRepository(UOM::class)->findOneByCode($data[self::IMPORT_HEADER_UOM]);

                if (!$uom instanceof UOM) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Uom']);
                }

                $hedgingTool = $this->em->getRepository(HedgingTool::class)->findOneBy(['name' => $data[self::IMPORT_HEADER_VENTURE],
                                                                                                'operationType' => array_search($operationType, Operations::$operationTypeForImportInverted)]);

                if (!$hedgingTool instanceof HedgingTool) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Hedging tool']);
                }

                $priceRiskClassification = $this->em->getRepository(PriceRiskClassification::class)->findOneByName($data[self::IMPORT_HEADER_PRICE_RISK_CLASSIFICATION]);

                if (!$priceRiskClassification instanceof PriceRiskClassification) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Price risk classification']);
                }

                $rmpSubSegment = $this->em->getRepository(RmpSubSegment::class)->findOneBy(['subSegment' => $subSegment, 'rmp' => $rmp]);

                if (!$rmpSubSegment instanceof RmpSubSegment) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Sub segment for the rmp']);
                } else {
                    if (!in_array($rmpSubSegment, $rmpSubSegments)) {
                        $rmpSubSegments[] = $rmpSubSegment;
                    }
                }

                $strategy = $this->em->getRepository(Strategy::class)->findOneByName($data[self::IMPORT_HEADER_STRATEGY]);

                if (!$strategy instanceof Strategy) {
                    $lineErrors[] = $this->translator->trans('import.hedges.error', ['%lineNumber%' => $lineNumber, '%element%' => 'Strategy']);
                }

                $hedgeCode = $data[self::IMPORT_HEADER_REF2];

                $hedge = $this->em->getRepository(Hedge::class)->findOneByCode($hedgeCode);
                $trade = $this->em->getRepository(Trade::class)->findOneByCxlTradeNumber($data[self::IMPORT_HEADER_CXL_TRADE_NUMBER]);

                $quantity = preg_replace('/[^a-zA-Z0-9-_\.]/','', $data[self::IMPORT_HEADER_QUANTITY]);
                if ($quantity < 0) {
                    $quantity = (float)$quantity * -1;
                } else {
                    $quantity = (float)$quantity;
                }

                if (!$trade instanceof Trade && !count($lineErrors)) {

                    $trade = new Trade();

                    $newHedge = false;
                    if (!$hedge instanceof Hedge) {
                        $newHedge = true;
                        $hedge = new Hedge();
                        $hedge->setProduct1($product1);
                        $hedge->setProduct2($product2);
                        $hedge->setCode($hedgeCode);
                        $hedge->setRmp($rmp);
                        $hedge->setCurrency($currency);
                        $hedge->setUom($uom);
                        $hedge->setHedgingTool($hedgingTool);
                        $hedge->setSubSegment($subSegment);
                        $hedge->setPriceRiskClassification($priceRiskClassification);
                        $hedge->setFirstMaturity($maturity);
                        $hedge->setLastMaturity($maturity);
                        $hedge->setOperationType(array_search($operationType, Operations::$operationTypeForImportInverted));
                        $hedge->setCreator($creator);
                        $hedge->setTotalVolume($hedge->getTotalVolume() + $quantity);
                        $hedge->setImported(true);

                        if ($data[self::IMPORT_HEADER_HEDGE_REALIZED] === '1') {
                            $hedge->setStatus(Hedge::STATUS_REALIZED);
                        } else {
                            $hedge->setStatus(Hedge::STATUS_PENDING_EXECUTION);
                            $hedge->setPartiallyRealized(true);
                        }

                        if (!empty($data[self::IMPORT_HEADER_WAIVER_CLASS_RISK])) {
                            $hedge->setWaiverClassRiskLevel(true);
                        }
                        $this->em->persist($hedge);

                    } else {
                        if ($maturity->getDate() < $hedge->getFirstMaturity()->getDate()) {
                            $hedge->setFirstMaturity($maturity);
                        } else if ($maturity->getDate() > $hedge->getLastMaturity()->getDate()) {
                            $hedge->setLastMaturity($maturity);
                        }
                    }

                    $this->em->flush();

                    $hedgeLine = $hedge->getHedgeLines()->first();

                    if ($newHedge) {
                        $this->logManager->createLog($hedge, $creator, HedgeLog::TYPE_IMPORT);
                    }

                    if (!$hedgeLine instanceof HedgeLine) {
                        $hedgeLine = new HedgeLine();
                        $hedgeLine->setHedge($hedge);
                        $hedgeLine->setQuantity($quantity);
                        $hedgeLine->setQuantityRealized($quantity);
                        $hedgeLine->setMaturity($maturity);
                        $hedgeLine->setStrategy($strategy);
                        $hedgeLine->setProtectionPrice($data[self::IMPORT_HEADER_PROTECTION_PRICE]);
                        $hedgeLine->setMaxLoss($data[self::IMPORT_HEADER_MAX_LOSS]);
                        $hedgeLine->setPremiumHedgingTool($data[self::IMPORT_HEADER_PREMIUM_HEDGING_TOOL]);
                        $hedgeLine->setRmpSubSegment($rmpSubSegment);

                        $waivers = $data[self::IMPORT_HEADER_WAIVERS];
                        if (!empty($waivers)) {
                            if ($waivers == HedgeLine::CODE_WAIVER_VOLUME || $waivers == HedgeLine::CODE_WAIVER_PRODUCT_VOLUME
                                || $waivers == HedgeLine::CODE_WAIVER_VOLUME_MATURITY) {
                                $hedgeLine->setWaiverVolume(true);
                            }

                            if ($waivers == HedgeLine::CODE_WAIVER_MATURITY || $waivers == HedgeLine::CODE_WAIVER_VOLUME_MATURITY
                                || $waivers == HedgeLine::CODE_WAIVER_PRODUCT_MATURITY) {
                                $hedgeLine->setWaiverMaturity(true);
                            }

                            if ($waivers == HedgeLine::CODE_WAIVER_PRODUCT || $waivers == HedgeLine::CODE_WAIVER_PRODUCT_MATURITY
                                || $waivers == HedgeLine::CODE_WAIVER_PRODUCT_VOLUME) {
                                $hedge->setWaiverProduct(true);
                            }
                        }
                        $this->em->persist($hedgeLine);
                    } else {
                        $oldTrades = $hedgeLine->getTrades();
                        if ($oldTrades->count() % count($hedgingTool->getOperationsAsArray()) == 0) {
                            $hedgeLine->setQuantityRealized($hedgeLine->getQuantityRealized() + $quantity);
                            $hedgeLine->setQuantity($hedgeLine->getQuantity() + $quantity);
                        }
                    }

                    $i = 0;
                    $isSetPremium = $isSetStrike = $isSetSwap = false;

                    $colPutCall = strtolower($data[self::IMPORT_HEADER_PUT_CALL]);
                    while ($i < 3) {
                        if (!empty($colPutCall)) {
                            $setter = 'set' . ucfirst($colPutCall);
                            $getter = 'get' . ucfirst($colPutCall);

                            if (!empty($data[self::IMPORT_HEADER_STRIKE]) && !$isSetStrike) {

                                $setterTrade = $setter . 'Strike';

                                if ($i == 0) {
                                    $getterStrike = $getter . 'Strike';
                                    $setterStrike = $setterTrade;
                                } else {
                                    $getterStrike =  $getter . $i . 'Strike';
                                    $setterStrike =  $setter . $i . 'Strike';
                                }

                                if (!$hedgeLine->$getterStrike()) {
                                    $hedgeLine->$setterStrike($data[self::IMPORT_HEADER_STRIKE]);
                                    $trade->$setterTrade($data[self::IMPORT_HEADER_STRIKE]);
                                    $isSetStrike = true;
                                }
                            }

                            if (!empty($data[self::IMPORT_HEADER_FIXED_PRICE])  && !$isSetPremium) {

                                $setterTrade = $setter . 'Premium';

                                if ($i == 0) {
                                    $getterPremium = $getter . 'Premium';
                                    $setterPremium = $setterTrade;
                                } else {
                                    $getterPremium =  $getter . $i . 'Premium';
                                    $setterPremium =  $setter . $i . 'Premium';
                                }

                                if (!$hedgeLine->$getterPremium()) {
                                    $hedgeLine->$setterPremium($data[self::IMPORT_HEADER_FIXED_PRICE]);
                                    $trade->$setterTrade($data[self::IMPORT_HEADER_FIXED_PRICE]);
                                    $isSetPremium = true;
                                }
                            }
                        } else {
                            if (!$isSetSwap) {

                                $setterTrade = 'setSwapPrice';
                                if ($i == 0) {
                                    $getterSwap = 'getSwapPrice';
                                    $setterSwap = $setterTrade;
                                } else {
                                    $getterSwap = 'getSwap' . $i . 'Price';
                                    $setterSwap = 'setSwap' . $i . 'Price';
                                }

                                if (!$hedgeLine->$getterSwap()) {
                                    $hedgeLine->$setterSwap($data[self::IMPORT_HEADER_FIXED_PRICE]);
                                    $trade->$setterTrade($data[self::IMPORT_HEADER_FIXED_PRICE]);
                                    $isSetSwap = true;
                                }
                            }
                        }
                        $i++;
                    }

                    $trade->setCxlTradeNumber($data[self::IMPORT_HEADER_CXL_TRADE_NUMBER]);
                    $trade->setTradingDate(new \DateTime($data[self::IMPORT_HEADER_TRADING_DATE]));
                    $trade->setQuantity($quantity);
                    $trade->setHedgeLine($hedgeLine);
                    $trade->setOperationType(array_search($operationType, Operations::$operationTypeChoices));
                    $trade->setInstrument(array_search($data[self::IMPORT_HEADER_PUT_CALL], Instruments::$instrumentType));
                    $trade->setStatus('Verified');

                    $hedgeLine->addTrade($trade);

                    $this->em->persist($trade);
                    $this->em->flush();

                    $this->hedgeVolumeManager->updateHedgeTotalVolume($hedge);
                }


                if (count($lineErrors)) {
                    $errors[$lineNumber] = $lineErrors;
                }

                $data = fgetcsv($content, 0, ",");
                $lineNumber++;
            }
        }

        foreach ($rmpSubSegments as $rmpSubSegment) {
            $this->rmpSubSegmentManager->calculateVolumes($rmpSubSegment);
        }

        return $errors;
    }
}
