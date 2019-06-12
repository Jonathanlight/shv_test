<?php

namespace App\Service;

use App\Constant\Operations;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentRiskLevel;
use App\Form\RmpSubSegmentRiskLevelType;
use App\Form\RmpSubSegmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class RmpSubSegmentManager
{

    private $em;
    private $formFactory;
    private $hedgeVolumeManager;
    private $uomConverterManager;

    /**
     * RmpManager constructor.
     * @param EntityManagerInterface $em
     * @param FormFactoryInterface $formFactory,
     * @param HedgeVolumeManager $hedgeVolumeManager
     * @param UomConverterManager $uomConverterManager
     */
    public function __construct(EntityManagerInterface $em, FormFactoryInterface $formFactory,
                                HedgeVolumeManager $hedgeVolumeManager, UomConverterManager $uomConverterManager)
    {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->hedgeVolumeManager = $hedgeVolumeManager;
        $this->uomConverterManager = $uomConverterManager;
    }


    /**
     * @param Request $request
     * @param RMP $rmp
     * @param RmpSubSegment $rmpSubSegment
     *
     * @return RmpSubSegment
     */
    public function submitRmpSubSegment(Request $request, RMP $rmp, RmpSubSegment $rmpSubSegment)
    {
        $rmpSubSegmentForm = $this->formFactory->create(RmpSubSegmentType::class, $rmpSubSegment, ['activeRmpSubSegments' => $rmp->getActiveRmpSubSegments()]);

        $rmpSubSegmentForm->handleRequest($request);

        if ($rmpSubSegmentForm->isSubmitted() && $rmpSubSegmentForm->isValid()) {
            $forms = [];
            $rmpSubSegmentRiskLevels = [];
            for ($i = 0; $i < 5; $i++) {
                $rmpSubSegmentRiskLevel = $rmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel($i) ?: new RmpSubSegmentRiskLevel();
                $rmpSubSegmentRiskLevel->setRiskLevel($i);
                $rmpSubSegmentRiskLevels[] = $rmpSubSegmentRiskLevel;
                $rmpSubSegmentRiskForm = $this->formFactory->createNamed('rmp_sub_segment_risk_level_'.$i, RmpSubSegmentRiskLevelType::class, $rmpSubSegmentRiskLevel);
                $forms[] = $rmpSubSegmentRiskForm;
            }

            foreach ($forms as $form) {
                $form->handleRequest($request);
            }

            foreach ($rmpSubSegmentRiskLevels as $_rmpSubSegmentRiskLevel) {
                $_rmpSubSegmentRiskLevel->setRmpSubSegment($rmpSubSegment);
                $rmpSubSegment->addRmpSubSegmentRiskLevel($_rmpSubSegmentRiskLevel);
            }

            if (!$rmpSubSegment->getRmp()) {
                $rmpSubSegment->setRmp($rmp);
            }

            $this->em->persist($rmpSubSegment);
            $this->em->flush();
        }

        return $rmpSubSegment;
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     */
    public function calculateVolumes(RmpSubSegment $rmpSubSegment)
    {
        foreach ($rmpSubSegment->getRmpSubSegmentRiskLevels() as $rmpSubSegmentRiskLevel) {
            $rmpSubSegmentRiskLevel->setConsumption(0);
            $rmpSubSegmentRiskLevel->setWaiverConsumption(0);
        }

        $hedgeLines = $this->em->getRepository(HedgeLine::class)->findRealizedByRmpSubSegment($rmpSubSegment);

        foreach ($hedgeLines as $hedgeLine) {
            $hedgingTool = $hedgeLine->getHedge()->getHedgingTool();

            $rmpRiskLevel0 = $hedgeLine->getRmpSubSegment()->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0);

            if ($hedgingTool->isSpecialStorageTools() && $rmpRiskLevel0->getMaximumVolume()
                && $hedgeLine->getHedge()->getOperationType() == Operations::OPERATION_TYPE_SELL) {
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
                $this->hedgeVolumeManager->updateConsumption($hedgeLine, $riskLevel, $quantityConverted, $riskLevel);
            }
        }

        $this->em->flush();
    }
}