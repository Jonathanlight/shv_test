<?php

namespace App\Service;

use App\Entity\Field;
use App\Entity\Hedge;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\UOM;
use App\Entity\Pricer;
use App\Entity\RMP;
use App\Entity\User;
use App\Form\FieldType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DashboardManager
{

    private $em;

    private $session;

    private $uomConverterManager;

    private $translator;

    private $container;

    /**
     * DashboardManager constructor.
     * @param EntityManagerInterface $em
     * @param SessionInterface $session
     * @param UomConverterManager $uomConverterManager
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, SessionInterface $session, UomConverterManager $uomConverterManager,
                                TranslatorInterface $translator, ContainerInterface $container)
    {
        $this->em = $em;
        $this->session = $session;
        $this->uomConverterManager = $uomConverterManager;
        $this->translator = $translator;
        $this->container = $container;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getStatsByUser(User $user): array
    {
        $hedgeRepository = $this->em->getRepository(Hedge::class);
        $rmpRepository = $this->em->getRepository(RMP::class);
        $dashboardStats = [];

        if ($user->isBuMember() || $user->isBuHedgingCommittee()) {
            // Hedge stats
            $businessUnit = $this->session->get('selectedBusinessUnit') ?: $user->getBusinessUnits()->first();
            $lastHedge = $hedgeRepository->findLastHedgeUpdated([$businessUnit]);
            $lastHedge = isset($lastHedge[0]) && $lastHedge[0] instanceof Hedge ? $lastHedge[0] : null;

            $hedgesPendingAction = $hedgeRepository->findByBusinessUnitsAndStatuses([$businessUnit], [Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER,
                                                                                                    Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER,
                                                                                                    Hedge::STATUS_PENDING_EXECUTION]);
            $dashboardStats['hedgeStats'] = $this->getHedgeStats($lastHedge, $hedgesPendingAction);

            // RMP stats
            $currentRmp = $rmpRepository->findFirstByBusinessUnits([$businessUnit]);
            $dashboardStats['rmpStats'] = $this->getBuRmpStats($currentRmp);

        } else if ($user->isRiskController() || $user->isTrader()) {
            // Hedge stats
            $lastHedge = $hedgeRepository->findLastHedgeUpdated();
            $lastHedge = isset($lastHedge[0]) && $lastHedge[0] instanceof Hedge ? $lastHedge[0] : null;
            $hedgesPendingAction = $hedgeRepository->findByStatus([Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER,
                                                                Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER,
                                                                Hedge::STATUS_PENDING_EXECUTION]);
            $dashboardStats['hedgeStats'] = $this->getHedgeStats($lastHedge, $hedgesPendingAction);

            // RMP stats
            $dashboardStats['rmpStats'] = $this->getGlobalRmpStats();
        } else {
            // Hedge stats
            $businessUnits = $user->getBusinessUnits()->toArray();
            $lastHedge = $hedgeRepository->findLastHedgeUpdated($businessUnits);
            $lastHedge = isset($lastHedge[0]) && $lastHedge[0] instanceof Hedge ? $lastHedge[0] : null;

            $hedgesPendingAction = $hedgeRepository->findByBusinessUnitsAndStatuses($businessUnits, [Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER,
                                                                                                    Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER,
                                                                                                    Hedge::STATUS_PENDING_EXECUTION]);
            $dashboardStats['hedgeStats'] = $this->getHedgeStats($lastHedge, $hedgesPendingAction);

            $currentRmp = $rmpRepository->findFirstByBusinessUnits($businessUnits);
            $dashboardStats['rmpStats'] = $this->getBuRmpStats($currentRmp);
            $dashboardStats['rmpStats'] = array_merge($this->getGlobalRmpStats($businessUnits), $dashboardStats['rmpStats']);
        }

        // Pricer stats
        $dashboardStats['pricerStats'] = $this->getPricerStats();


        return $dashboardStats;
    }

    /**
     * @return array
     */
    private function getPricerStats(): array
    {
        $pricer = $this->em->getRepository(Pricer::class)->findLastPricer();

        if (count($pricer) && $pricer[0] instanceof Pricer) {
            $pricerStats['lastDateUpdated'] = $pricer[0]->getUpdatedAt();
        }

        $pricerInfoForms = [];
        foreach (Field::$pricerFields as $fieldCode) {
            $field = $this->em->getRepository(Field::class)->findOneByCode($fieldCode);
            if (!$field instanceof Field) {
                $field = new Field();
                $field->setCode($fieldCode);
            }

            $form = $this->container->get('form.factory')->createNamed(str_replace('.', '_', $fieldCode), FieldType::class, $field);
            $pricerInfoForms[$fieldCode] = [
                'form' => $form->createView(),
                'fieldInfos' => $field,
            ];
        }

        $pricerStats['fields'] = $pricerInfoForms;

        return $pricerStats;
    }

    /**
     * @param Hedge|null $lastHedge
     * @param array $hedgesPendingAction
     *
     * @return array
     */
    private function getHedgeStats(?Hedge $lastHedge, array $hedgesPendingAction): array
    {
        $hedgeStats = ['pendingApprovalRiskController' => 0,
                        'pendingApprovalBoardMember' => 0,
                        'pendingExecution' => 0];

        if ($lastHedge instanceof Hedge) {
            $hedgeStats['lastDateUpdated'] = $lastHedge->getUpdatedAt();
        }

        foreach ($hedgesPendingAction as $hedge) {
            if ($hedge->getStatus() == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
                $hedgeStats['pendingApprovalRiskController']++;
            } else if ($hedge->getStatus() == Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER) {
                $hedgeStats['pendingApprovalBoardMember']++;
            } else {
                $hedgeStats['pendingExecution']++;
            }
        }

        return $hedgeStats;
    }

    /**
     * @param RMP $currentRmp
     * @return array
     */
    private function getBuRmpStats(?RMP $currentRmp): array
    {
        $rmpStats = [];

        if ($currentRmp instanceof RMP) {
            $rmpStats = ['rmpName' => $currentRmp->getName(), 'lastDateUpdated' => $currentRmp->getUpdatedAt()];
        }

        $hedges = $this->em->getRepository(Hedge::class)->findBy(['status' => Hedge::STATUS_REALIZED, 'rmp' => $currentRmp]);
        $uomMt = $this->em->getRepository(UOM::class)->findOneByCode(UOM::BASE_UOM_CODE);

        $totalRealized = 0;
        foreach ($hedges as $hedge) {
            foreach ($hedge->getHedgeLines() as $hedgeLine) {
                if ($currentRmp->getActiveRmpSubSegments()->contains($hedgeLine->getRmpSubSegment())) {
                    $totalRealized += (double)$this->uomConverterManager->convert($hedgeLine->getQuantityRealized(),
                                                                                    $hedge->getProduct1()->getCommodity(),
                                                                                    $hedge->getUom(),
                                                                                    $uomMt);
                }
            }
        }

        $rmpStats['totalRealized'] = $totalRealized;

        $totalPlanned = 0;
        if ($currentRmp instanceof RMP) {
            foreach ($currentRmp->getActiveRmpSubSegments() as $rmpSubSegment) {
                if ($rmpSubSegment->getProducts()->count()) {
                    $totalPlanned += (double)$this->uomConverterManager->convert($rmpSubSegment->getMaximumVolume(),
                        $rmpSubSegment->getProducts()->first()->getCommodity(),
                        $rmpSubSegment->getUom(),
                        $uomMt);
                }
            }

        }

        $rmpStats['totalPlanned'] = $totalPlanned;
        $rmpStats['totalRealizedPercent'] = $totalRealized > 0 ? $totalRealized / $totalPlanned * 100 : 0;

        if ($currentRmp->isAmendment()) {
            $rmpStats['status'] = $this->translator->trans('rmp.dashboard.amendment');
        } else if ($currentRmp->isApprovedAutomatically()) {
            $rmpStats['status'] = $this->translator->trans('rmp.dashboard.approved_automatically');
        } else {
            $rmpStats['status'] = $this->translator->trans('rmp.dashboard.approved');
        }

        return $rmpStats;
    }

    /**
     * @param array $businessUnits
     * @return array
     */
    private function getGlobalRmpStats(array $businessUnits = null): array
    {
        $rmpRepository = $this->em->getRepository(RMP::class);
        $rmpStats = ['pendingApprovalRiskController' => 0,
                    'pendingApprovalBoardMember' => 0];

        $rmpsPendingAction = $rmpRepository->findByStatusesFromNow([RMP::STATUS_PENDING_APPROVAL_BOARD_MEMBER, RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER], $businessUnits);

        foreach ($rmpsPendingAction as $rmp) {
            if ($rmp->getStatus() == RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
                $rmpStats['pendingApprovalRiskController']++;
            } else {
                $rmpStats['pendingApprovalBoardMember']++;
            }
        }

        $lastRmpUpdated = $rmpRepository->findLastUpdated();
        $rmpStats['rmpName'] = $lastRmpUpdated->getName();
        $rmpStats['lastDateUpdated'] = $lastRmpUpdated->getUpdatedAt();

        return $rmpStats;
    }
}