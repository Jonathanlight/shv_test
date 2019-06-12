<?php

namespace App\Service;

use App\Entity\Hedge;
use App\Entity\HedgeLog;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentRiskLevel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RmpManager
{
    protected $em;

    protected $logManager;

    protected $tokenStorage;

    protected $notificationManager;

    protected $router;

    /**
     * RmpManager constructor.
     * @param EntityManagerInterface $em
     * @param LogManager $logManager
     * @param TokenStorageInterface $tokenStorage
     * @param NotificationManager $notificationManager
     * @param RouterInterface $router
     */
    public function __construct(EntityManagerInterface $em, LogManager $logManager, TokenStorageInterface $tokenStorage,
                                NotificationManager $notificationManager, RouterInterface $router)
    {
        $this->em = $em;

        $this->logManager = $logManager;

        $this->tokenStorage = $tokenStorage;

        $this->notificationManager = $notificationManager;

        $this->router = $router;
    }

    /**
     * @param RMP $rmp
     * @return RMP
     */
    public function createAmendment(RMP $rmp): RMP
    {
        $newRmp = clone $rmp;
        $newRmp->setStatus(RMP::STATUS_DRAFT);
        $newRmp->setCopiedFrom($rmp);

        if (!$newRmp->isApprovedAutomatically()) {
            $newRmp->setAmendment(true);
        }

        $newRmp->setName($newRmp->getName() . ' - DRAFT');
        $newRmp->setApprovedAutomatically(false);
        $newRmp->setCreator($this->tokenStorage->getToken()->getUser());

        $this->em->persist($newRmp);
        $this->em->flush();

        return $newRmp;
    }

    /**
     * @param RMP $rmp
     * @return RMP
     */
    public function renewRmp(RMP $rmp): RMP
    {
        $newRmp = clone $rmp;
        $newRmp->setVersion(1);
        $newRmp->setStatus(RMP::STATUS_APPROVED);
        $newRmp->setApprovedAutomatically(true);
        $newRmp->setValidityPeriod($rmp->getValidityPeriod()+1);
        $newRmp->setCopiedFrom(null);
        $newRmp->setAmendment(false);

        $this->em->persist($newRmp);
        $this->em->flush();

        return $newRmp;
    }

    /**
     * @param BusinessUnit $businessUnit
     */
    public function createBlankRmpsByBusinessUnit(BusinessUnit $businessUnit)
    {
        for ($i = 0; $i < 4; $i++) {
            $year = date('Y', strtotime('+'.$i.' years'));

            $rmp = new RMP();
            $rmp->setStatus(RMP::STATUS_APPROVED);
            $rmp->setName('RM Policy_' . str_replace(array('.', ' '), array('_', '_'), $businessUnit->getFullName()) . '_' . $year . '_V1');
            $rmp->setValidityPeriod($year);
            $rmp->setBusinessUnit($businessUnit);
            $rmp->setActive(1);
            $rmp->setAmendment(0);
            if ($i == 0) {
                $rmp->setN3Exists(1);
            }

            $this->em->persist($rmp);
        }

        $this->em->flush();
    }

    /**
     * @param RMP $rmp
     */
    public function mergeRmp(RMP $rmp)
    {
        $oldRmp = $this->em->getRepository(RMP::class)->findOneBy(['businessUnit' => $rmp->getBusinessUnit(),
                                                                            'status' => RMP::STATUS_APPROVED,
                                                                            'validityPeriod' => $rmp->getValidityPeriod()]);

        $hedges = $this->em->getRepository(Hedge::class)->findByRmp($oldRmp);

        foreach ($hedges as $hedge) {
            $hedge->setRmp($rmp);

            if ($hedge->isPendingApproval()) {
                $currentRmpSubSegment = $rmp->getActiveRmpSubSegmentBySubSegment($hedge->getSubSegment());
                if (!$currentRmpSubSegment instanceof RmpSubSegment) {
                    $hedge->setStatus(Hedge::STATUS_CANCELED);
                    $this->logManager->createLog($hedge, null, HedgeLog::TYPE_CANCELED_AUTOMATICALLY);

                    $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_CANCELLED_AUTOMATICALLY,
                                                                $hedge,
                                                                [$hedge->getCreator()],
                                                                ['hedgeId' => $hedge->getId(),
                                                                'url' => $this->router->generate('hedge_edit',
                                                                                            ['hedge' => $hedge->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)]);
                }
            }

            foreach ($hedge->getHedgeLines() as $hedgeLine) {
                $oldRmpSubSegment = $hedgeLine->getRmpSubSegment();

                if (!$hedgeLine->getFirstRmpSubSegment()) {
                    $hedgeLine->setFirstRmpSubSegment($oldRmpSubSegment);
                }
                $hedgeLine->setRmpSubSegment($this->getNewRmpSubSegment($rmp, $oldRmpSubSegment));
            }

            $this->em->persist($hedge);
        }

        $oldRmp->setStatus(RMP::STATUS_ARCHIVED);
        $this->em->persist($oldRmp);

        if ($this->tokenStorage->getToken()) {
            $this->logManager->createLog($oldRmp, $this->tokenStorage->getToken()->getUser(), RMPLog::TYPE_ARCHIVED);
        }


        $rmp->setStatus(RMP::STATUS_APPROVED);
        $rmp->setVersion($oldRmp->getVersion()+1);

        $rmpName = $rmp->getName();

        $rmpName = preg_replace('/(\s-\sDRAFT)$/', '', $rmpName);

        if (preg_match('/(_V[0-9]+)$/', $rmpName)) {
            $rmpName = preg_replace('/(_V[0-9]+)$/', '_V'.$rmp->getVersion(), $rmpName);
        } else {
            $rmpName = $rmp->getName().'_V'.$rmp->getVersion();
        }

        $rmp->setName($rmpName);

        $this->em->persist($rmp);

        $this->impactNextApprovedAutomaticallyRmp($rmp);
        $this->updateRmpSubSegmentsOnMerge($rmp, $oldRmp);

        $this->em->flush();
    }

    /**
     * @param RMP $rmp
     * @param RmpSubSegment $oldRmpSubSegment
     * @return RmpSubSegment
     */
    private function getNewRmpSubSegment(RMP $rmp, RmpSubSegment $oldRmpSubSegment): ?RmpSubSegment
    {
        $newRmpSubSegment = $oldRmpSubSegment;

        foreach ($rmp->getActiveRmpSubSegments() as $rmpSubSegment) {
            if ($rmpSubSegment->getSubSegment()->getId() == $oldRmpSubSegment->getSubSegment()->getId()) {
                $newRmpSubSegment = $rmpSubSegment;
            }
        }

        return $newRmpSubSegment;
    }

    /**
     * @param RMP $firstRmp
     * @param RMP $secondRmp
     * @return array
     */
    public function compareRmps(RMP $firstRmp, RMP $secondRmp): array
    {
        $differences = [
            'generalComment' => [],
            'rmpSubSegmentRemoved' => [],
            'rmpSubSegmentAdded' => [],
            'segmentsNew' => [],
            'segmentUpdated' => [],
            'rmpSubSegment' => [],
            'keyViewTab' => [],
            'commentsTab' => [],
            'hedgingToolsTab' => [],
        ];

        // Compare RMP infos
        if ($firstRmp->getGeneralComment() != $secondRmp->getGeneralComment()) {
            $differences['generalComment'] = true;
        }

        // Compare RMP sub Segments add / remove
        $firstRmpSubSegmentIds = [];
        foreach ($firstRmp->getRmpSubSegments() as $rmpSubSegment) {
            if (!$rmpSubSegment->isActive()) {
                continue;
            }
            $firstRmpSubSegmentIds[] = $rmpSubSegment->getSubSegment()->getId();
        }

        $secondRmpSubSegmentIds = [];
        foreach ($secondRmp->getRmpSubSegments() as $rmpSubSegment) {
            if (!$rmpSubSegment->isActive()) {
                continue;
            }
            $secondRmpSubSegmentIds[] = $rmpSubSegment->getSubSegment()->getId();
        }

        $differences['rmpSubSegmentRemoved'] =  array_diff($firstRmpSubSegmentIds, $secondRmpSubSegmentIds);
        $differences['rmpSubSegmentAdded'] = array_diff($secondRmpSubSegmentIds, $firstRmpSubSegmentIds);

        $differences['segmentsNew'] = [];
        if (count($differences['rmpSubSegmentRemoved']) || count($differences['rmpSubSegmentAdded'])) {
            foreach ($differences['rmpSubSegmentAdded'] as $subSegmentId) {
                $subSegment = $this->em->getRepository(SubSegment::class)->find($subSegmentId);
                if (!in_array($subSegment->getSegment()->getId(), $differences['segmentsNew'])) {
                    $differences['segmentUpdated'][] = $subSegment->getSegment()->getId();
                }
            }

            foreach ($differences['rmpSubSegmentRemoved'] as $subSegmentId) {
                $subSegment = $this->em->getRepository(SubSegment::class)->find($subSegmentId);
                if (!in_array($subSegment->getSegment()->getId(), $differences['segmentsNew'])) {
                    $differences['segmentUpdated'][] = $subSegment->getSegment()->getId();
                }
            }
        }

        // Compare RMP sub segments modifications
        foreach ($secondRmp->getActiveRmpSubSegments() as $rmpSubSegment) {
            $subSegmentId = $rmpSubSegment->getSubSegment()->getId();
            if (in_array($subSegmentId, $differences['rmpSubSegmentAdded'])) {
                $differences['rmpSubSegment'][$rmpSubSegment->getId()] = [];
                continue;
            }

            $oldRmpSubSegment = null;
            $oldActiveRmpSubSegments = $firstRmp->getActiveRmpSubSegments();

            foreach ($oldActiveRmpSubSegments as $oldActiveRmpSubSegment) {
                if ($oldActiveRmpSubSegment->getSubSegment()->getId() == $subSegmentId) {
                    $oldRmpSubSegment = $oldActiveRmpSubSegment;
                }
            }

            $rmpSubSegmentDifferences = $this->compareRmpSubSegments($oldRmpSubSegment, $rmpSubSegment);
            $differences['rmpSubSegment'][$rmpSubSegment->getId()] = $rmpSubSegmentDifferences['rmpSubSegmentDifferences'];

            $segmentId = $rmpSubSegment->getSubSegment()->getSegment()->getId();
            if (count($rmpSubSegmentDifferences['rmpSubSegmentDifferences'])
                && !in_array($segmentId, $differences['segmentUpdated'])) {
                $differences['segmentUpdated'][] = $segmentId;
            }

            foreach (['keyViewTab', 'commentsTab', 'hedgingToolsTab'] as $tab) {
                if (isset($rmpSubSegmentDifferences['tabDifferences'][$tab])) {
                    $differences[$tab] = true;
                    if (!isset($differences['segmentsTabInfos']) || !isset($differences['segmentsTabInfos'][$rmpSubSegment->getSubSegment()->getSegment()->getId()])) {
                        $differences['segmentsTabInfos'][$rmpSubSegment->getSubSegment()->getSegment()->getId()][] = $tab;
                    }
                }
            }
        }

        return $differences;
    }

    /**
     * @param RmpSubSegment $firstRmpSubSegment
     * @param RmpSubSegment $secondRmpSubSegment
     *
     * @return array
     */
    private function compareRmpSubSegments(RmpSubSegment $firstRmpSubSegment, RmpSubSegment $secondRmpSubSegment): array
    {
        // General RMP Sub Segment infos
        $rmpSubSegmentDifferences = [];
        $tabDifferences = [];

        foreach (RmpSubSegment::$fieldsToCompare as $field) {
            $function = 'get'.ucfirst($field);
            if ($firstRmpSubSegment->$function() != $secondRmpSubSegment->$function()) {
                $rmpSubSegmentDifferences[$field] = true;

                if (in_array($field, RmpSubSegment::$fieldsKeyViewTab)) {
                    $tabDifferences['keyViewTab'] = true;
                }

                if (in_array($field, RmpSubSegment::$fieldsCommentsTab)) {
                    $tabDifferences['commentsTab'] = true;
                }
            }
        }

        // Products
        $oldProductsId = [];
        foreach ($firstRmpSubSegment->getProducts() as $product) {
            $oldProductsId[] = $product->getId();
        }

        $newProductsId = [];
        foreach ($secondRmpSubSegment->getProducts() as $product) {
            $newProductsId[] = $product->getId();
        }

        $productsRemoved = array_diff($oldProductsId, $newProductsId);
        $productsAdded = array_diff($newProductsId, $oldProductsId);

        if (count($productsRemoved) || count($productsAdded)) {
            $rmpSubSegmentDifferences['products'] = true;
            $tabDifferences['commentsTab'] = true;
        }

        // RMP Sub Segment Risk Levels
        for ($i = 0; $i < 5; $i++) {
            if ($firstRmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel($i)->getMaximumVolume() !=
                $secondRmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel($i)->getMaximumVolume()) {
                $rmpSubSegmentDifferences['riskLevel'.$i] = true;
                $tabDifferences['hedgingToolsTab'] = true;
            }
        }

        return array('rmpSubSegmentDifferences' => $rmpSubSegmentDifferences, 'tabDifferences' => $tabDifferences);
    }

    /**
     * @param RMP $newRmp
     * @param RMP $oldRmp
     */
    private function updateRmpSubSegmentsOnMerge(RMP $newRmp, RMP $oldRmp)
    {
        foreach ($newRmp->getActiveRmpSubSegments() as $newRmpSubSegment) {
            $oldRmpSubSegment = $oldRmp->getActiveRmpSubSegmentBySubSegment($newRmpSubSegment->getSubSegment());
            if ($oldRmpSubSegment instanceof RmpSubSegment) {
                $newRmpSubSegment = $this->updateRmpSubSegmentVersion($newRmpSubSegment, $oldRmpSubSegment);
                foreach ($newRmpSubSegment->getRmpSubSegmentRiskLevels() as $newRmpSubSegmentRiskLevel) {
                    $oldRmpSubSegmentRiskLevel = $oldRmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel($newRmpSubSegmentRiskLevel->getRiskLevel());
                    $newRmpSubSegmentRiskLevel->setConsumption($oldRmpSubSegmentRiskLevel->getConsumption());
                    $newRmpSubSegmentRiskLevel->setWaiverConsumption($oldRmpSubSegmentRiskLevel->getWaiverConsumption());
                    $this->em->persist($newRmpSubSegmentRiskLevel);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * @param RmpSubSegment $newRmpSubSegment
     * @param RmpSubSegment $oldRmpSubSegment
     * @return RmpSubSegment
     */
    private function updateRmpSubSegmentVersion(RmpSubSegment $newRmpSubSegment, RmpSubSegment $oldRmpSubSegment): RmpSubSegment
    {
        if ($oldRmpSubSegment instanceof RmpSubSegment) {
            $differences = $this->compareRmpSubSegments($oldRmpSubSegment, $newRmpSubSegment);
            if (count($differences['rmpSubSegmentDifferences'])) {
                $newRmpSubSegment->setVersion($oldRmpSubSegment->getVersion()+1);
                $this->em->persist($newRmpSubSegment);
            }
        }

        return $newRmpSubSegment;
    }

    /**
     * @param RMP $rmp
     */
    private function impactNextApprovedAutomaticallyRmp(RMP $rmp)
    {
        $subSegmentRepository = $this->em->getRepository(SubSegment::class);
        $rmpSubSegmentRepository = $this->em->getRepository(RmpSubSegment::class);

        $nextApprovedAutomatically = $this->em->getRepository(RMP::class)->findNextApprovedAutomatically($rmp);
        if (isset($nextApprovedAutomatically[0]) && $nextApprovedAutomatically[0] instanceof RMP) {
            $nextApprovedAutomatically = $nextApprovedAutomatically[0];
            $differences = $this->compareRmps($nextApprovedAutomatically, $rmp);
            foreach ($differences['rmpSubSegmentAdded'] as $subSegmentId) {
                $subSegment = $subSegmentRepository->find($subSegmentId);
                $newRmpSubSegment = clone $rmp->getActiveRmpSubSegmentBySubSegment($subSegment);
                $newRmpSubSegment->setRmp($nextApprovedAutomatically);
                $this->em->persist($newRmpSubSegment);
                $nextApprovedAutomatically->addRmpSubSegment($newRmpSubSegment);
            }

            foreach ($differences['rmpSubSegmentRemoved'] as $subSegmentId) {
                $subSegment = $subSegmentRepository->find($subSegmentId);
                $oldRmpSubSegment = $nextApprovedAutomatically->getActiveRmpSubSegmentBySubSegment($subSegment);
                $oldRmpSubSegment->setActive(false);
                $this->em->persist($oldRmpSubSegment);
            }

            foreach ($differences['rmpSubSegment'] as $rmpSubSegmentId => $rmpSubSegmentDiff) {
                foreach ($rmpSubSegmentDiff as $fieldDiff => $value) {
                    $rmpSubSegment = $rmpSubSegmentRepository->find($rmpSubSegmentId);
                    $oldRmpSubSegment = $nextApprovedAutomatically->getActiveRmpSubSegmentBySubSegment($rmpSubSegment->getSubSegment());
                    if (preg_match('/^(riskLevel)/', $fieldDiff)) {
                        $rmpSubSegmentRiskLevel = $rmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel(substr($fieldDiff, -1));
                        $oldRmpSubSegmentRiskLevel = $oldRmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel(substr($fieldDiff, -1));
                        $oldRmpSubSegmentRiskLevel->setMaximumVolume($rmpSubSegmentRiskLevel->getMaximumVolume());
                        $this->em->persist($oldRmpSubSegmentRiskLevel);
                    } else {
                        $getFunction = 'get'.ucfirst($fieldDiff);
                        $setFunction = 'set'.ucfirst($fieldDiff);
                        $oldRmpSubSegment->$setFunction($rmpSubSegment->$getFunction());
                    }
                    $this->em->persist($oldRmpSubSegment);
                }
            }
            $this->em->persist($nextApprovedAutomatically);
            $this->em->flush();
            $this->impactNextApprovedAutomaticallyRmp($nextApprovedAutomatically);
        }
    }
}