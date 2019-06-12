<?php

namespace App\Service;

use App\Constant\Operations;
use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentRiskLevel;
use Doctrine\ORM\EntityManagerInterface;

class HedgeVolumeManager
{
    private $em;

    private $uomConverterManager;

    /**
     * HedgeVolumeManager constructor.
     * @param EntityManagerInterface $em
     * @param UomConverterManager $uomConverterManager
     */
    public function __construct(EntityManagerInterface $em, UomConverterManager $uomConverterManager)
    {
        $this->em = $em;
        $this->uomConverterManager = $uomConverterManager;
    }

    /**
     * @param Hedge $hedge
     */
    public function updateVolumesByHedge(Hedge $hedge)
    {
        $hedgingTool = $hedge->getHedgingTool();

        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            $rmpRiskLevel0 = $hedgeLine->getRmpSubSegment()->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0);

            if ($hedgingTool->isSpecialStorageTools() && $rmpRiskLevel0->getMaximumVolume() && $hedge->getOperationType() == Operations::OPERATION_TYPE_SELL) {
                $riskLevel = HedgingTool::RISK_LEVEL_0;
            } else {
                $riskLevel = $hedgingTool->getRiskLevel();
            }

            $quantityConverted = $this->uomConverterManager->convert($hedgeLine->getQuantityRealized(),
                                                                    $hedgeLine->getHedge()->getProduct1()->getCommodity(),
                                                                    $hedgeLine->getHedge()->getUom(),
                                                                    $hedgeLine->getRmpSubSegment()->getUom());

            if ($hedgeLine->isWaiverVolume()) {
                $rmpSubSegmentRiskLevel = $this->em->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
                    'rmpSubSegment' => $hedgeLine->getRmpSubSegment(),
                    'riskLevel' => $riskLevel
                ]);

                if ($hedgeLine->isDecreasingVolume()) {
                    $rmpSubSegmentRiskLevel->addWaiverConsumption($quantityConverted);
                } else {
                    $rmpSubSegmentRiskLevel->removeWaiverConsumption($quantityConverted);
                }
            } else {
                $this->updateConsumption($hedgeLine, $riskLevel, $quantityConverted, $riskLevel);
            }
        }

        $this->em->flush();
    }


    /**
     * @param HedgeLine $hedgeLine
     * @param int $riskLevel
     * @param float $quantity
     * @param int $initialRiskLevel
     */
    public function updateConsumption(HedgeLine $hedgeLine, int $riskLevel, float $quantity, int $initialRiskLevel)
    {
        $rmpSubSegmentRiskLevel = $this->em->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
            'rmpSubSegment' => $hedgeLine->getRmpSubSegment(),
            'riskLevel' => $riskLevel
        ]);

        $volumeAllowed = $rmpSubSegmentRiskLevel->getMaximumVolume() - $rmpSubSegmentRiskLevel->getConsumption();
        if ($hedgeLine->isDecreasingVolume()) {
            if ($volumeAllowed >= $quantity) {
                $rmpSubSegmentRiskLevel->addConsumption($quantity);
            } else {
                $rmpSubSegmentRiskLevel->addConsumption($volumeAllowed);
                $quantityConverted = $this->uomConverterManager->convert($hedgeLine->getQuantityRealized(),
                                                                         $hedgeLine->getHedge()->getProduct1()->getCommodity(),
                                                                         $hedgeLine->getHedge()->getUom(),
                                                                         $hedgeLine->getRmpSubSegment()->getUom());

                $volumeLeft = $quantityConverted - $volumeAllowed;

                $this->em->persist($rmpSubSegmentRiskLevel);
                $newRiskLevel = $riskLevel-1;
                if ($newRiskLevel > 0) {
                    $this->updateConsumption($hedgeLine, $newRiskLevel, $volumeLeft, $initialRiskLevel);
                } else {
                    $initialRmpSubSegmentRiskLevel = $this->em->getRepository(RmpSubSegmentRiskLevel::class)->findOneBy([
                        'rmpSubSegment' => $hedgeLine->getRmpSubSegment(),
                        'riskLevel' => $initialRiskLevel
                    ]);
                    $initialRmpSubSegmentRiskLevel->addConsumption($volumeLeft);
                }
            }
        } else {
            $rmpSubSegmentRiskLevel->removeConsumption($hedgeLine->getQuantityRealized());
        }

        $this->em->persist($rmpSubSegmentRiskLevel);
    }

    /**
     * @param Hedge $hedge
     */
    public function updateHedgeTotalVolume(Hedge $hedge)
    {
        $totalVolume = 0;
        foreach ($hedge->getHedgeLines() as $k => $hedgeLine) {
            $totalVolume += $hedgeLine->getQuantity();
        }
        $hedge->setTotalVolume($totalVolume);
    }
}