<?php

namespace App\Service;

use App\Constant\Operations;
use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Product;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentRiskLevel;
use Doctrine\ORM\EntityManagerInterface;

class HedgeLimitManager
{
    private $em;

    private $yearN;

    private $yearN1;

    private $yearN2;

    private $yearN3;

    private $maturityManager;

    private $uomConverterManager;

    /**
     * HedgeLimitManager constructor.
     * @param EntityManagerInterface $em
     * @param MaturityManager $maturityManager
     * @param UomConverterManager $uomConverterManager
     */
    public function __construct(EntityManagerInterface $em, MaturityManager $maturityManager, UomConverterManager $uomConverterManager)
    {
        $this->em = $em;
        $this->maturityManager = $maturityManager;
        $this->uomConverterManager = $uomConverterManager;
    }

    public function setYears(Hedge $hedge)
    {
        $this->yearN = $hedge->getRmp()->getValidityPeriod();
        $this->yearN1 = date('Y', strtotime(date('Y', strtotime($this->yearN)) . ' + 1 year'));
        $this->yearN2 = date('Y', strtotime(date('Y', strtotime($this->yearN)) . ' + 2 year'));
        $this->yearN3 = date('Y', strtotime(date('Y', strtotime($this->yearN)) . ' + 3 year'));
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    public function getVolumeAndLimitDetails(Hedge $hedge): array
    {
        $this->setYears($hedge);
        $openLimits = $this->getOpenLimitsVolume($hedge);

        $rmpSubSegment = $this->em->getRepository(RmpSubSegment::class)->findOneBy([
            'rmp' => $hedge->getRmp(),
            'subSegment' => $hedge->getSubSegment()
        ]);

        return [
            'hedgingToolDetails' => $this->getHedgingToolDetails($hedge),
            'productDetails' => $this->getProductDetails($hedge, $rmpSubSegment),
            'openLimits' => $openLimits,
            'requestHedging' => $this->getRequestHedgingVolume($hedge),
            'volumeWaivers' => $this->getHedgeLinesVolumeWaivers($hedge, $openLimits),
            'maturityWaivers' => $this->getHedgeLinesMaturityWaivers($hedge),
            'classRiskLevelDetails' => $this->getClassRiskLevelDetails($hedge),
            'maturityDetails' => $this->getMaturityDetails($hedge, $rmpSubSegment),
        ];
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    private function getClassRiskLevelDetails(Hedge $hedge): array
    {
        $hedgingTool = $hedge->getHedgingTool();
        $rmpSubSegment = $this->em->getRepository(RmpSubSegment::class)->findOneBy(['rmp' => $hedge->getRmp(), 'subSegment' => $hedge->getSubSegment()]);
        $rmpSubSegmentRiskLevels = $rmpSubSegment->getRmpSubSegmentRiskLevels();

        $isStorageTools = false;
        $maximumRiskLevel = $rmpSubSegment->getMaximumRiskLevel();

        foreach ($rmpSubSegmentRiskLevels as $rmpSubSegmentRiskLevel) {
            if ($rmpSubSegmentRiskLevel->getRiskLevel() == HedgingTool::RISK_LEVEL_0 && $rmpSubSegmentRiskLevel->getMaximumVolume()) {
                $isStorageTools = true;
            }
        }

        $waiverClassRisk = false;
        if (($isStorageTools && ($hedgingTool->getRiskLevel() != HedgingTool::RISK_LEVEL_0 && !$hedgingTool->isSpecialStorageTools())) || $hedgingTool->getRiskLevel() < $maximumRiskLevel) {
            $waiverClassRisk = true;
        }

        $classRiskLevelLabel = HedgingTool::$riskLevelsLabels[$hedge->getHedgingTool()->getRiskLevel()];
        $classRiskLevelLabel .= $hedgingTool->isSpecialStorageTools() ? ' / ' . HedgingTool::$riskLevelsLabels[HedgingTool::RISK_LEVEL_0] : '';

        return [
            'classRiskLevel' => $classRiskLevelLabel,
            'waiverClassRisk' => $waiverClassRisk
        ];
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    private function getHedgingToolDetails(Hedge $hedge): array
    {
        return [
            'operationTypes' => implode(' and ', $hedge->getHedgingTool()->getOperationsAsArray()),
        ];
    }

    /**
     * @param Hedge $hedge
     * @param RmpSubSegment $rmpSubSegment
     * @return array
     */
    private function getMaturityDetails(Hedge $hedge, RmpSubSegment $rmpSubSegment): array
    {
        $waiverMaturity = false;
        $lastHedgeLine = $hedge->getHedgeLines()->last();
        if ($this->maturityManager->getMaturityIntervalByRmp($lastHedgeLine->getMaturity()) > $rmpSubSegment->getMaximumMaturities()) {
            $waiverMaturity = true;
        }

        return [
            'maximumMaturities' => $rmpSubSegment->getMaximumMaturities(),
            'waiverMaturity' => $waiverMaturity
        ];
    }

    /**
     * @param Hedge $hedge
     * @param RmpSubSegment $rmpSubSegment
     * @return array
     */
    private function getProductDetails(Hedge $hedge, RmpSubSegment $rmpSubSegment): array
    {
        $product1 = $hedge->getProduct1();
        $product2 = $hedge->getProduct2();

        $waiverProduct = false;
        $waiverProduct1Id = $waiverProduct2Id = 0;
        $rmpSubSegmentProducts = $rmpSubSegment->getProducts();

        if ($product1 && !$rmpSubSegmentProducts->contains($product1)) {
            $waiverProduct = true;
            $waiverProduct1Id = $product1->getId();
        }

        if ($product2 && !$rmpSubSegmentProducts->contains($product2)) {
            $waiverProduct = true;
            $waiverProduct2Id = $product2->getId();
        }

        return [
            'products' => implode('<br>', $rmpSubSegment->getProductsNamesAsArray()),
            'waiverProduct' => $waiverProduct,
            'waiverProduct1Id' => $waiverProduct1Id,
            'waiverProduct2Id' => $waiverProduct2Id
        ];
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    private function getRequestHedgingVolume(Hedge $hedge): array
    {
        $hedgeUom = $hedge->getUom()->getCode();

        $requestHedging = [
            'n' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN],
            'n1' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN1],
            'n2' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN2],
            'n3' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN3],
            'total' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => null]
        ];

        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            $rmpSubSegment = $hedgeLine->getRmpSubSegment();
            $rmp = $rmpSubSegment->getRmp();
            $year = $rmp->getValidityPeriod();

            switch ($year) {
                case $this->yearN:
                    $requestHedging['n']['volume'] += $hedgeLine->getQuantity();
                    break;
                case $this->yearN1:
                    $requestHedging['n1']['volume'] += $hedgeLine->getQuantity();
                    break;
                case $this->yearN2:
                    $requestHedging['n2']['volume'] += $hedgeLine->getQuantity();
                    break;
                case $this->yearN3:
                    $requestHedging['n3']['volume'] += $hedgeLine->getQuantity();
                    break;
                default:
                    break;
            }
            $requestHedging['total']['volume'] += $hedgeLine->getQuantity();
        }

        return [
            'n' => $requestHedging['n'],
            'n1' => $requestHedging['n1'],
            'n2' => $requestHedging['n2'],
            'n3' => $requestHedging['n3'],
            'total' => $requestHedging['total']
        ];
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    private function getOpenLimitsVolume(Hedge $hedge): array
    {
        $yearCalculated = [];
        $hedgeUom = $hedge->getUom()->getCode();

        $openLimit = [
            'n' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN],
            'n1' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN1],
            'n2' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN2],
            'n3' => ['uom' => $hedgeUom, 'volume' => 0, 'year' => $this->yearN3],
        ];

        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            $rmpSubSegment = $hedgeLine->getRmpSubSegment();
            $rmp = $rmpSubSegment->getRmp();
            $year = $rmp->getValidityPeriod();
            $riskLevel = $hedge->getHedgingTool()->getRiskLevel();
            $rmpSubSegmentRiskLevels = $this->em->getRepository(RmpSubSegmentRiskLevel::class)->findByRmpSubSegment($rmpSubSegment);

            if (!$hedgeLine->getRmpSubSegment() || in_array($year, $yearCalculated)) {
                continue;
            }

            $hedges = $this->em->getRepository(Hedge::class)
                     ->findByRmpSubSegmentAndStatuses($rmpSubSegment, array(Hedge::STATUS_PENDING_EXECUTION), $riskLevel);

            $rmpSubSegmentRiskLevelsVolumes = $this->calculateOpenHedgingLimit($rmpSubSegmentRiskLevels, $hedges, $year, $hedge);

            if ($rmpSubSegment->getMaximumRiskLevel() == HedgingTool::RISK_LEVEL_0 && $hedge->getHedgingTool()->isSpecialStorageTools()) {
                $selectedRiskLevel = HedgingTool::RISK_LEVEL_0;
            } else {
                $selectedRiskLevel = $hedge->getHedgingTool()->getRiskLevel();
            }

            switch ($year) {
                case $this->yearN:
                    $openLimit['n']['volume'] = $rmpSubSegmentRiskLevelsVolumes[$selectedRiskLevel];
                    break;
                case $this->yearN1:
                    $openLimit['n1']['volume'] = $rmpSubSegmentRiskLevelsVolumes[$selectedRiskLevel];
                    break;
                case $this->yearN2:
                    $openLimit['n2']['volume'] = $rmpSubSegmentRiskLevelsVolumes[$selectedRiskLevel];
                    break;
                case $this->yearN3:
                    $openLimit['n3']['volume'] = $rmpSubSegmentRiskLevelsVolumes[$selectedRiskLevel];
                    break;
                default:
                    break;
            }
        }

        return [
            'n' => $openLimit['n'],
            'n1' => $openLimit['n1'],
            'n2' => $openLimit['n2'],
            'n3' => $openLimit['n3']
        ];
    }

    /**
     * @param array $rmpSubSegmentRiskLevels
     * @param array $hedges
     * @param int $year
     * @param Hedge $hedge
     * @return array|null
     */
    private function calculateOpenHedgingLimit(array $rmpSubSegmentRiskLevels, array $hedges, int $year, Hedge $hedge): array
    {
        foreach ($rmpSubSegmentRiskLevels as $rmpSubSegmentRiskLevel) {
            $maxVolumeConverted = $this->uomConverterManager->convert($rmpSubSegmentRiskLevel->getMaximumVolume(), $hedge->getProduct1()->getCommodity(),
                                                                      $rmpSubSegmentRiskLevel->getRmpSubSegment()->getUom(), $hedge->getUom());
            $consumptionConverted =  $this->uomConverterManager->convert($rmpSubSegmentRiskLevel->getConsumption(), $hedge->getProduct1()->getCommodity(),
                                                                        $rmpSubSegmentRiskLevel->getRmpSubSegment()->getUom(), $hedge->getUom());
            $rmpSubSegmentRiskLevelsVolumes[$rmpSubSegmentRiskLevel->getRiskLevel()] = $maxVolumeConverted - $consumptionConverted;

        }

        foreach ($hedges as $_hedge) {
            foreach($_hedge->getHedgeLines() as $_hedgeLine) {
                if ($_hedgeLine->getRmpSubSegment()->getRmp()->getValidityPeriod() == $year && !$_hedgeLine->isWaiverVolume()) {

                    $quantityConverted = $this->uomConverterManager->convert($_hedgeLine->getQuantity(),
                                                                            $_hedgeLine->getHedge()->getProduct1()->getCommodity(),
                                                                            $_hedgeLine->getHedge()->getUom(),
                                                                            $hedge->getUom());

                    $currentRiskLevel = $_hedgeLine->getCurrentRiskLevel();
                    if ($_hedgeLine->isDecreasingVolume()) {
                        if ($rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel] - $quantityConverted < 0) {
                            $extraVolume = $quantityConverted - $rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel];
                            $rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel] = 0;
                            if ($currentRiskLevel > HedgingTool::RISK_LEVEL_1) {
                                $rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel-1] -= $extraVolume;
                            }
                        } else {
                            $rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel] -= $quantityConverted;
                        }
                    } else {
                        $rmpSubSegmentRiskLevelsVolumes[$currentRiskLevel] += $quantityConverted;
                    }
                }
            }
        }

        foreach ($rmpSubSegmentRiskLevelsVolumes as $riskLevel => $volume) {
            if ($riskLevel === 0) {
                continue;
            }
            $rmpSubSegmentRiskLevelsVolumes[$riskLevel] += $rmpSubSegmentRiskLevelsVolumes[$riskLevel-1];
        }

        return $rmpSubSegmentRiskLevelsVolumes;
    }

    /**
     * @param Hedge $hedge
     * @param array $openLimits
     * @return array
     */
    private function getHedgeLinesVolumeWaivers(Hedge $hedge, array $openLimits): array
    {
        $totalVolume =  [
            'n' => 0,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
        ];

        $waivers = [];

        foreach ($hedge->getHedgeLines() as $hedgeLine) {

            $currentRiskLevel = $hedgeLine->getCurrentRiskLevel();

            $skipWaiverVolume = $volumeAvailable = false;

            $rmpSubSegmentRiskLevel0 = $hedgeLine->getRmpSubSegment()->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0);
            if (($currentRiskLevel == HedgingTool::RISK_LEVEL_0 && $rmpSubSegmentRiskLevel0->getMaximumVolume())
            || ($currentRiskLevel != HedgingTool::RISK_LEVEL_0 && !$rmpSubSegmentRiskLevel0->getMaximumVolume())) {
                $volumeAvailable = true;
            }

            if ($volumeAvailable && !$hedgeLine->isDecreasingVolume()) {
                $skipWaiverVolume = true;
            }

            if (!$hedgeLine->getRmpSubSegment()
                || ($hedge->getOperationType() == Operations::OPERATION_TYPE_SELL && $skipWaiverVolume)) {
                continue;
            }

            $year = $hedgeLine->getRmpSubSegment()->getRmp()->getValidityPeriod();

            switch ($year) {
                case $this->yearN:
                    $totalVolume['n'] += $hedgeLine->getQuantity();
                    if ($totalVolume['n'] > $openLimits['n']['volume']) {
                        $waivers[] = $hedgeLine->getMaturity()->getId();
                    }
                    break;
                case $this->yearN1:
                    $totalVolume['n1'] += $hedgeLine->getQuantity();
                    if ($totalVolume['n1'] > $openLimits['n1']['volume']) {
                        $waivers[] = $hedgeLine->getMaturity()->getId();
                    }
                    break;
                case $this->yearN2:
                    $totalVolume['n2'] += $hedgeLine->getQuantity();
                    if ($totalVolume['n2'] > $openLimits['n2']['volume']) {
                        $waivers[] = $hedgeLine->getMaturity()->getId();
                    }
                    break;
                case $this->yearN3:
                    $totalVolume['n3'] += $hedgeLine->getQuantity();
                    if ($totalVolume['n3'] > $openLimits['n3']['volume']) {
                        $waivers[] = $hedgeLine->getMaturity()->getId();
                    }
                    break;
                default:
                    break;
            }
        }

        return $waivers;
    }

    /**
     * @param Hedge $hedge
     * @return array
     */
    private function getHedgeLinesMaturityWaivers(Hedge $hedge): array
    {
        $rmpSubSegment = $this->em->getRepository(RmpSubSegment::class)->findOneBy([
            'rmp' => $hedge->getRmp(),
            'subSegment' => $hedge->getSubSegment()
        ]);

        $waivers = [];
        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            $hedgeLineMaturity = $hedgeLine->getMaturity();
            if ($this->maturityManager->getMaturityIntervalByRmp($hedgeLineMaturity) > $rmpSubSegment->getMaximumMaturities()) {
                $waivers[] = $hedgeLineMaturity->getId();
            }
        }

        return $waivers;
    }

    /**
     * @param Hedge $hedge
     * @return bool
     */
    public function isWaiver(Hedge $hedge): bool
    {
        $volumesAndLimits = $this->getVolumeAndLimitDetails($hedge);

        return $volumesAndLimits['productDetails']['waiverProduct']
            || count($volumesAndLimits['volumeWaivers'])
            || count($volumesAndLimits['maturityWaivers'])
            || $volumesAndLimits['classRiskLevelDetails']['waiverClassRisk']
            || $volumesAndLimits['maturityDetails']['waiverMaturity'];
    }

    /**
     * @param Hedge $hedge
     * @return bool
     */
    public function isWaiverProduct(Hedge $hedge): bool
    {
        $volumesAndLimits = $this->getVolumeAndLimitDetails($hedge);

        return $volumesAndLimits['productDetails']['waiverProduct'];
    }

    /**
     * @param Hedge $hedge
     * @return bool
     */
    public function isWaiverClassRiskLevel(Hedge $hedge): bool
    {
        $volumesAndLimits = $this->getVolumeAndLimitDetails($hedge);

        return $volumesAndLimits['classRiskLevelDetails']['waiverClassRisk'];
    }

    /**
     * @param HedgeLine $hedgeLine
     * @return bool
     */
    public function isWaiverMaturity(HedgeLine $hedgeLine): bool
    {
        $volumesAndLimits = $this->getVolumeAndLimitDetails($hedgeLine->getHedge());

        return in_array($hedgeLine->getMaturity()->getId(), $volumesAndLimits['maturityWaivers']);
    }

    /**
     * @param HedgeLine $hedgeLine
     * @return bool
     */
    public function isWaiverVolume(HedgeLine $hedgeLine): bool
    {
        $volumesAndLimits = $this->getVolumeAndLimitDetails($hedgeLine->getHedge());

        return in_array($hedgeLine->getMaturity()->getId(), $volumesAndLimits['volumeWaivers']);
    }

    /**
     * @param Hedge $hedge
     * @return bool
     */
    public function isSellExtraApproval(Hedge $hedge): bool
    {
        $isExtraApproval = true;
        $firstHedgeLine = $hedge->getHedgeLines()->first();

        $volumeAndLimits = $this->getVolumeAndLimitDetails($hedge);
        $volumeWaivers = $volumeAndLimits['volumeWaivers'];

        $currentRmpSubSegmentRiskLevel = $this->em->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy(['rmpSubSegment' => $firstHedgeLine->getRmpSubSegment(), 'riskLevel' => $hedge->getHedgingTool()->getRiskLevel()]);
        $rmpSubSegmentRiskLevel0 = $firstHedgeLine->getRmpSubSegment()->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0);

        if ($hedge->getOperationType() == Operations::OPERATION_TYPE_SELL
            && in_array($hedge->getHedgingTool()->getCode(), HedgingTool::$storageToolsHedgingTool)
            && $rmpSubSegmentRiskLevel0->getMaximumVolume() > 0
            && ($currentRmpSubSegmentRiskLevel->getRiskLevel() == HedgingTool::RISK_LEVEL_0 || $hedge->getHedgingTool()->isSpecialStorageTools())
            && !count($volumeWaivers)) {

            $isExtraApproval = false;
        }

        return $isExtraApproval;
    }

    /**
     * @param Hedge $hedge
     * @return bool
     */
    public function isSellExtraVolume(Hedge $hedge): bool
    {
        $isSellExtraVolume = false;

        if ($hedge->getOperationType() == Operations::OPERATION_TYPE_SELL) {
            $totalVolumeByRmpSubSegment = [];
            $totalVolumeAllowedByRmpSubSegment = [];
            foreach ($hedge->getHedgeLines() as $hedgeLine) {
                $currentRmpSubSegment = $hedgeLine->getRmpSubSegment();

                if (!isset($totalVolumeByRmpSubSegment[$currentRmpSubSegment->getId()])) {
                    $totalVolumeByRmpSubSegment[$currentRmpSubSegment->getId()] = 0;
                }

                $totalVolumeByRmpSubSegment[$hedgeLine->getRmpSubSegment()->getId()] += $hedgeLine->getQuantity();
                $maximumRiskLevel = $currentRmpSubSegment->getMaximumRiskLevel();

                if ($maximumRiskLevel == HedgingTool::RISK_LEVEL_0) {
                    $volumeAndLimits = $this->getVolumeAndLimitDetails($hedge);
                    switch ($hedgeLine->getMaturity()->getYear()) {
                        case $this->yearN:
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] = $volumeAndLimits['openLimits']['n']['volume'];
                            break;
                        case $this->yearN1:
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] = $volumeAndLimits['openLimits']['n1']['volume'];
                            break;
                        case $this->yearN2:
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] = $volumeAndLimits['openLimits']['n2']['volume'];
                            break;
                        case $this->yearN3:
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] = $volumeAndLimits['openLimits']['n3']['volume'];
                            break;
                        default:
                            break;
                    }
                } else {
                    foreach ($currentRmpSubSegment->getRmpSubSegmentRiskLevels() as $rmpSubSegmentRiskLevel) {
                        if (!isset($totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()])) {
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] = 0;
                        }
                        if ($rmpSubSegmentRiskLevel->getRiskLevel() <= $maximumRiskLevel && $rmpSubSegmentRiskLevel->getRiskLevel() != HedgingTool::RISK_LEVEL_0) {
                            $totalVolumeAllowedByRmpSubSegment[$currentRmpSubSegment->getId()] += $rmpSubSegmentRiskLevel->getConsumption();
                        }
                    }
                }
            }

            foreach ($totalVolumeByRmpSubSegment as $rmpSubSegmentId => $totalVolume) {
                if ($totalVolume > $totalVolumeAllowedByRmpSubSegment[$rmpSubSegmentId]) {
                    $isSellExtraVolume = true;
                }
            }
        }

        return $isSellExtraVolume;
    }
}