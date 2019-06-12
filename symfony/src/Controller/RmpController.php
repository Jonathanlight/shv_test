<?php

namespace App\Controller;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\Segment;
use App\Entity\MasterData\UOM;
use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentComment;
use App\Entity\RmpValidation;
use App\Entity\User;
use App\Form\CommentType;
use App\Service\ListFiltersManager;
use App\Service\LogManager;
use App\Service\NotificationManager;
use App\Service\UomConverterManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\RmpType;
use App\Service\RmpManager;
use App\Service\RmpSubSegmentManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\MasterData\Commodity;

class RmpController extends Controller
{
    private $logManager;

    private $notificationManager;

    /**
     * HedgeController constructor.
     * @param LogManager $logManager
     * @param NotificationManager $notificationManager
     */
    public function __construct(LogManager $logManager, NotificationManager $notificationManager)
    {
        $this->logManager = $logManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @Route(path="/rmps/{keepFilters}", name="rmp_list", methods={"GET"})
     *
     * @Security("is_granted(constant('\\App\\Security\\Voter\\RmpVoter::RMP_VIEW'))")
     *
     * @param Request $request
     * @param string $keepFilters
     * @param ListFiltersManager $listFiltersManager
     *
     * @return Response
     */
    public function rmpListAction(Request $request, string $keepFilters = '', ListFiltersManager $listFiltersManager): Response
    {
        $route = $request->get('_route');
        $doctrine = $this->getDoctrine();
        $user = $this->getUser();
        $businessUnits = $user->getBusinessUnits()->count() ? $user->getBusinessUnits() : $doctrine->getRepository(BusinessUnit::class)->findBy([], ['fullName' => 'ASC']);

        for ($i = 0; $i < 4; $i++) {
            $validityPeriods[] = date('Y', strtotime('+'.$i.' years'));
        }

        $filters = null;
        if ($keepFilters) {
            $filters = $listFiltersManager->getFilters($route);
        }

        return $this->render('rmp/list.html.twig', [
            'businessUnits' => $businessUnits,
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'validityPeriods' => $validityPeriods,
            'route' => $route,
            'filters' => $filters,
            'flagLabels' => RMP::$flagLabels,
            'rmpStatuses' => RMP::$statusLabels
        ]);
    }

    /**
     * @Route("/rmp/{rmp}/view/{segment}", name="rmp_view", requirements={"id" = "\d+"}, defaults={"segment"=0}, methods={"GET", "POST"})
     *
     * @Security("is_granted(constant('\\App\\Security\\Voter\\RmpVoter::RMP_VIEW'))")
     *
     * @param Request $request
     * @param RMP $rmp
     * @param RmpManager $rmpManager
     * @param Segment|null $segment
     * @param UomConverterManager $uomConverterManager
     *
     * @return Response
     */
    public function rmpEditAction(Request $request, RMP $rmp, RmpManager $rmpManager, ?Segment $segment, UomConverterManager $uomConverterManager): Response
    {
        $rmpRepository = $this->getDoctrine()->getRepository(RMP::class);

        $currentYear = (int)date('Y');
        $validityPeriodNtoN3 = [];

        while (count($validityPeriodNtoN3) < 4) {
            $validityPeriodNtoN3[] = $currentYear++;
        }

        $history = $rmpRepository->findForHistory($rmp);
        $businessUnitUsers = $this->getDoctrine()->getRepository(User::class)->findByRmp($rmp);

        $rmpForm = $this->createForm(RmpType::class, $rmp);

        $previousRmp = $this->getDoctrine()->getRepository(RMP::class)->findOneBy(['validityPeriod' => $rmp->getValidityPeriod() - 1, 'businessUnit' => $rmp->getBusinessUnit(), 'status' => RMP::STATUS_APPROVED], ['version' => 'DESC']);
        $pairedRmpSubSegments = [];
        $comments = [];

        if ($rmp->isPendingApproval()) {
            $rmpSubSegments = $rmp->getRmpSubSegments();
        } else {
            $rmpSubSegments = $rmp->getActiveRmpSubSegments();
        }

        $uomMt = $this->getDoctrine()->getRepository(UOM::class)->findOneByCode(UOM::BASE_UOM_CODE);
        $segments = [];
        $totalSalesVolume = $totalVolumeAtRisk = 0;
        $totalRisk = [0, 0, 0, 0, 0];
        $totalPerSegment = [];

        foreach ($rmpSubSegments as $_rmpSubSegment) {
            $segment = $_rmpSubSegment->getSubSegment()->getSegment();
            if (!in_array($_rmpSubSegment->getSubSegment()->getSegment(), $segments)) {
                $segments[] = $segment;
            }
            $comments[$_rmpSubSegment->getId()]['comments'] = $this->getDoctrine()->getRepository(RmpSubSegmentComment::class)->findBy(['parent' => $_rmpSubSegment], ['id' => 'DESC']);

            if ($previousRmp instanceof RMP) {
                $previousRmpSubSegment = $this->getDoctrine()->getRepository(RmpSubSegment::class)->findLastYearRmpSubSegment($_rmpSubSegment, $previousRmp);
            } else {
                $previousRmpSubSegment = null;
            }

            $pairedRmpSubSegments[] = [
                'current' => $_rmpSubSegment,
                'previous' => $previousRmpSubSegment && count($previousRmpSubSegment) ? $previousRmpSubSegment[0] : null,
            ];

            $products  = $_rmpSubSegment->getProducts();
            $commodity = $products->isEmpty() ? new Commodity() : $products->first()->getCommodity();

            if ($_rmpSubSegment->isActive()) {
                $totalSalesVolume += (double)$uomConverterManager->convert(
                    $_rmpSubSegment->getSalesVolume(),
                    $commodity,
                    $_rmpSubSegment->getUom(),
                    $uomMt
                );
                $totalVolumeAtRisk += (double)$uomConverterManager->convert(
                    $_rmpSubSegment->getMaximumVolume(),
                    $commodity,
                    $_rmpSubSegment->getUom(),
                    $uomMt
                );

                if (!isset($totalPerSegment[$segment->getId()])) {
                    $totalPerSegment[$segment->getId()] = [
                        'salesVolume' => (double)$uomConverterManager->convert(
                            $_rmpSubSegment->getSalesVolume(),
                            $commodity,
                            $_rmpSubSegment->getUom(),
                            $uomMt
                        ),
                        'maxVolumeAtRisk' => (double)$uomConverterManager->convert(
                            $_rmpSubSegment->getMaximumVolume(),
                            $commodity,
                            $_rmpSubSegment->getUom(),
                            $uomMt
                        ),
                    ];
                } else {
                    $totalPerSegment[$segment->getId()]['salesVolume'] += (double)$uomConverterManager->convert($_rmpSubSegment->getSalesVolume(), $commodity, $_rmpSubSegment->getUom(), $uomMt);
                    $totalPerSegment[$segment->getId()]['maxVolumeAtRisk'] += (double)$uomConverterManager->convert(
                        $_rmpSubSegment->getMaximumVolume(),
                        $commodity,
                        $_rmpSubSegment->getUom(),
                        $uomMt
                    );
                }

                foreach ($_rmpSubSegment->getRmpSubSegmentRiskLevels() as $rmpSubSegmentRiskLevel) {
                    $totalRisk[$rmpSubSegmentRiskLevel->getRiskLevel()] += (double)$uomConverterManager->convert(
                        $rmpSubSegmentRiskLevel->getMaximumVolume(),
                        $commodity,
                        $_rmpSubSegment->getUom(),
                        $uomMt
                    );

                    if (!isset($totalPerSegment[$segment->getId()]['risk' . $rmpSubSegmentRiskLevel->getRiskLevel()])) {
                        $totalPerSegment[$segment->getId()]['risk' . $rmpSubSegmentRiskLevel->getRiskLevel()] = (double)$uomConverterManager->convert(
                            $rmpSubSegmentRiskLevel->getMaximumVolume(),
                            $commodity,
                            $_rmpSubSegment->getUom(),
                            $uomMt
                        );
                    } else {
                        $totalPerSegment[$segment->getId()]['risk' . $rmpSubSegmentRiskLevel->getRiskLevel()] += (double)$uomConverterManager->convert(
                            $rmpSubSegmentRiskLevel->getMaximumVolume(),
                            $commodity,
                            $_rmpSubSegment->getUom(),
                            $uomMt
                        );
                    }
                }
            }
        }

        usort($segments, function ($a, $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        if ($rmp->isPendingApproval()) {
            $oldRmp = $this->getDoctrine()->getRepository(RMP::class)->findOneBy([
                'businessUnit' => $rmp->getBusinessUnit(),
                'status' => RMP::STATUS_APPROVED,
                'validityPeriod' => $rmp->getValidityPeriod()
            ]);
            $differences = $rmpManager->compareRmps($oldRmp, $rmp);
        } else {
            $differences = array('rmpSubSegmentRemoved' => [], 'rmpSubSegmentAdded' => []);
        }

        $rmpForm->handleRequest($request);
        if ($rmpForm->isSubmitted() && $rmpForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($rmp);
            $em->flush();

            return $this->redirectToRoute('rmp_view', ['rmp' => $rmp->getId()]);
        }

        if ($rmp->isPendingApproval()) {
            $formRefuseComment = $this->get('form.factory')->createNamed('comment_refuse', CommentType::class, null, [
                'action' => $this->generateUrl('rmp_refuse', ['rmp' => $rmp->getId()]),
            ]);
            $formAcceptComment = $this->get('form.factory')->createNamed('comment_accept', CommentType::class, null, [
                'action' => $this->generateUrl('rmp_accept', ['rmp' => $rmp->getId()]),
            ]);
        }

        $nextApprovedAutomatically = $this->getDoctrine()->getRepository(RMP::class)->findNextApprovedAutomatically($rmp);
        if (isset($nextApprovedAutomatically[0])) {
            $nextApprovedAutomatically = $nextApprovedAutomatically[0];
        }

        $logs = $this->getDoctrine()->getRepository(RMPLog::class)->findBy(['parent' => $rmp], ['timestamp' => 'DESC']);

        return $this->render('rmp/view.html.twig', [
            'segments' => $segments,
            'segment' => $segment,
            'validityPeriodNtoN3' => $validityPeriodNtoN3,
            'history' => $history,
            'rmp' => $rmp,
            'logs' => $logs,
            'logsActionsLabels' => RMPLog::$typeActionsLabels,
            'rmpForm' => $rmpForm->createView(),
            'rmpSubSegments' => $pairedRmpSubSegments,
            'comments' => $comments,
            'businessUnitUsers' => $businessUnitUsers,
            'differences' => $differences,
            'totalSalesVolume' => $totalSalesVolume,
            'totalPerSegment' => $totalPerSegment,
            'totalVolumeAtRisk' => $totalVolumeAtRisk,
            'totalRisk' => $totalRisk,
            'nextApprovedAutomatically' => $nextApprovedAutomatically instanceof RMP ? true : false,
            'formRefuseComment' => isset($formRefuseComment) ? $formRefuseComment->createView() : null,
            'formAcceptComment' => isset($formAcceptComment) ? $formAcceptComment->createView() : null,
        ]);
    }

    /**
     * @Route("/rmp/{rmp}/edit/{rmpSubSegment}", name="rmp_edit_rmp_sub_segment", methods={"GET", "POST"})
     *
     * @isGranted("rmp_edit", subject="rmp")
     *
     * @param Request $request
     * @param RMP $rmp
     * @param RmpSubSegment $rmpSubSegment
     * @param RmpSubSegmentManager $rmpSubSegmentManager
     *
     * @return Response
     */
    public function rmpSubSegmentEditAction(Request $request, RMP $rmp, RmpSubSegment $rmpSubSegment, RmpSubSegmentManager $rmpSubSegmentManager)
    {
        $rmpSubSegment = $rmpSubSegmentManager->submitRmpSubSegment($request, $rmp, $rmpSubSegment);

        return $this->redirectToRoute('rmp_view', ['rmp' => $rmp->getId(), 'segment' => $rmpSubSegment->getSubSegment()->getSegment()->getId()]);
    }

    /**
     * @Route("/rmp/{rmp}/create", name="rmp_create_rmp_sub_segment", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param RMP $rmp
     * @param RmpSubSegmentManager $rmpSubSegmentManager
     *
     * @isGranted("rmp_edit", subject="rmp")
     *
     * @return Response
     */
    public function rmpSubSegmentCreateAction(Request $request, RMP $rmp, RmpSubSegmentManager $rmpSubSegmentManager)
    {
        $rmpSubSegment = new RmpSubSegment();

        $rmpSubSegment = $rmpSubSegmentManager->submitRmpSubSegment($request, $rmp, $rmpSubSegment);

        return $this->redirectToRoute('rmp_view', ['rmp' => $rmp->getId(), 'segment' => $rmpSubSegment->getSubSegment()->getSegment()->getId()]);
    }

    /**
     * @Route(path="/rmp/{rmp}/draft", name="rmp_create_draft")
     *
     * @isGranted("rmp_draft", subject="rmp")
     *
     * @param RmpManager $rmpManager
     * @param RMP $rmp
     *
     * @return Response
     */
    public function rmpDuplicateAction(RMP $rmp, RmpManager $rmpManager): Response
    {
        $newRmp = $rmpManager->createAmendment($rmp);

        return $this->redirectToRoute('rmp_view', ['rmp' => $newRmp->getId()]);
    }

    /**
     * @Route(path="/rmp/{rmp}/refuse", name="rmp_refuse")
     *
     * @isGranted("rmp_validate", subject="rmp")
     *
     * @param Request $request
     * @param RMP $rmp
     * @return Response
     */
    public function rmpRefuseAction(Request $request, RMP $rmp)
    {
        $formComment = $this->get('form.factory')->createNamed('comment_refuse', CommentType::class, null, [
            'action' => $this->generateUrl('rmp_refuse', ['rmp'=> $rmp->getId()]),
        ]);

        $formComment->handleRequest($request);
        $user = $this->getUser();

        if ($formComment->isSubmitted() && $formComment->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $rmp->setStatus(RMP::STATUS_DRAFT);
            $em->persist($rmp);
            $em->flush();

            $formData = $formComment->getData();
            if ($user->hasRole(User::ROLE_RISK_CONTROLLER)) {
                $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_REJECTED_RISK_CONTROLLER,
                                                            $rmp,
                                                            [$rmp->getCreator()],
                                                            ['rmpName' => $rmp->getName(),
                                                            'generalComment' => $formData['message'],
                                                            'url' => $this->generateUrl('rmp_view',
                                                                                        ['rmp' => $rmp->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)],
                                                            $formData['message']);

                $this->logManager->createLog($rmp, $user, RMPLog::TYPE_REJECTED_RISK_CONTROLLER, $formData['message']);
            } else {
                $riskControllers = $this->getDoctrine()->getRepository(User::class)->findByRole(User::ROLE_RISK_CONTROLLER);
                $users = array_merge($riskControllers, [$rmp->getCreator()]);

                foreach ($users as $_user) {
                    $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_REJECTED_BOARD_MEMBER,
                                                                $rmp,
                                                                [$_user],
                                                                ['rmpName' => $rmp->getName(),
                                                                'generalComment' => $formData['message'],
                                                                'url' => $this->generateUrl('rmp_view',
                                                                                            ['rmp' => $rmp->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)],
                                                                $formData['message']);
                }

                $this->getDoctrine()->getRepository(RmpValidation::class)->disablePreviousValidations($rmp);
                $this->logManager->createLog($rmp, $user, RMPLog::TYPE_APPROVED_BOARD_MEMBER, $formData['message']);
            }

            return $this->redirectToRoute('rmp_list');
        }

        return $this->redirectToRoute('rmp_view', ['rmp' => $rmp->getId()]);
    }

    /**
     * @Route(path="/rmp/{rmp}/accept", name="rmp_accept")
     *
     * @isGranted("rmp_validate", subject="rmp")
     *
     * @param Request $request
     * @param RMP $rmp
     * @param  RmpManager $rmpManager
     *
     * @return Response
     */
    public function rmpAcceptAction(Request $request, RMP $rmp, RmpManager $rmpManager)
    {
        $formComment = $this->get('form.factory')->createNamed('comment_accept', CommentType::class, null, [
            'action' => $this->generateUrl('rmp_accept', ['rmp'=> $rmp->getId()]),
        ]);
        $formComment->handleRequest($request);
        $user = $this->getUser();

        if ($formComment->isSubmitted() && $formComment->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $formData = $formComment->getData();

            if ($user->hasRole(User::ROLE_RISK_CONTROLLER)) {
                $rmp->setStatus(RMP::STATUS_PENDING_APPROVAL_BOARD_MEMBER);

                $boardMembers = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $rmp->getBusinessUnit());

                if ($rmp->isAmendment()) {
                    $notificationType = NotificationManager::TYPE_RMP_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER;
                } else {
                    $notificationType = NotificationManager::TYPE_RMP_PENDING_APPROVAL_BOARD_MEMBER;
                }

                foreach ($boardMembers as $boardMember) {
                    $this->notificationManager->sendNotification($notificationType,
                                                                $rmp,
                                                                [$boardMember],
                                                                ['rmpName' => $rmp->getName(),
                                                                'generalComment' => $formData['message'],
                                                                'url' => $this->generateUrl('rmp_view',
                                                                                            ['rmp' => $rmp->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)],
                                                                $formData['message']);
                }

                $this->logManager->createLog($rmp, $this->getUser(), RMPLog::TYPE_APPROVED_RISK_CONTROLLER, $formData['message']);
            } else {
                $rmpValidationRepository = $this->getDoctrine()->getRepository(RmpValidation::class);

                $businessUnit = $rmp->getBusinessUnit();
                $boardMembers = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $businessUnit);
                $rmpValidations = $rmpValidationRepository->findActivesByRmp($rmp);
                $userRmpValidation = $rmpValidationRepository->findOneBy(['rmp' => $rmp, 'user' => $this->getUser(), 'active' => 1]);

                if (!$userRmpValidation instanceof RmpValidation) {
                    $rmpValidation = new RmpValidation();
                    $rmpValidation->setRmp($rmp);
                    $rmpValidation->setUser($this->getUser());

                    $em->persist($rmpValidation);
                    $this->logManager->createLog($rmp, $this->getUser(), RMPLog::TYPE_APPROVED_BOARD_MEMBER, $formData['message']);
                }

                if (count($rmpValidations)+1 == count($boardMembers)) {
                    $rmpManager->mergeRmp($rmp);

                    $users = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER, User::ROLE_BU_HEDGING_COMMITTEE],
                                                                                                                        $rmp->getBusinessUnit(),
                                                                                                                        $this->getUser());
                    $riskControllers = $this->getDoctrine()->getRepository(User::class)->findByRole(User::ROLE_RISK_CONTROLLER);
                    $users = array_merge($users, $riskControllers);
                    $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_APPROVED,
                                                                $rmp,
                                                                $users,
                                                                ['rmpName' => $rmp->getName(),
                                                                'url' => $this->generateUrl('rmp_view',
                                                                                            ['rmp' => $rmp->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)],
                                                                $formData['message']);
                }

            }

            $em->persist($rmp);
            $em->flush();

            return $this->redirectToRoute('rmp_list');
        }

        return $this->redirectToRoute('rmp_view', ['rmp' => $rmp->getId()]);
    }
}
