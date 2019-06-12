<?php

namespace App\Controller;

use App\Constant\Operations;
use App\Entity\CMS\Letter;
use App\Entity\Hedge;
use App\Entity\HedgeComment;
use App\Entity\HedgeLine;
use App\Entity\HedgeLog;
use App\Entity\RmpValidation;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\Product;
use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\MasterData\UOM;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\HedgeType;
use App\Service\CommentManager;
use App\Service\HedgeLimitManager;
use App\Service\ListFiltersManager;
use App\Service\Excel\ExcelManager;
use App\Service\Excel\SheetContents\ApoSheet;
use App\Service\Excel\SheetContents\SwapSheet;
use App\Service\LogManager;
use App\Service\NotificationManager;
use App\Service\UomConverterManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HedgeController extends AbstractController
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
     * @Route(path="/hedges/{keepFilters}", name="hedge_list", methods={"GET"})
     * @param Request $request
     * @param string $keepFilters
     * @param ListFiltersManager $listFiltersManager
     * @return Response
     */
    public function hedgeListAction(Request $request, string $keepFilters = '', ListFiltersManager $listFiltersManager): Response
    {
        $route = $request->get('_route');
        $doctrine = $this->getDoctrine();
        $segments = $doctrine->getRepository(Segment::class)->findByActiveAndUsed();
        $subSegments = $doctrine->getRepository(SubSegment::class)->findByActiveAndUsed();
        $products = $doctrine->getRepository(Product::class)->findByActive(1, ['name' => 'ASC']);
        $maturities = $doctrine->getRepository(Maturity::class)->findFromNow();
        $hedgingTools = $doctrine->getRepository(HedgingTool::class)->findByActive(1, ['name' => 'ASC']);
        $user = $this->getUser();
        $businessUnits = $user->getBusinessUnits()->count() ? $user->getBusinessUnits() : $doctrine->getRepository(BusinessUnit::class)->findByActive(1, ['fullName' => 'ASC']);

        $selectedBusinessUnit = null;
        if ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE)) {
            $selectedBusinessUnit = $request->get('selectedBusinessUnit');
        }

        $filters = null;
        if ($keepFilters) {
            $filters = $listFiltersManager->getFilters($route);
        }

        return $this->render('hedge/list.html.twig', [
            'hedgeStatuses' => $user->hasRole(User::ROLE_RISK_CONTROLLER) || $user->hasRole(User::ROLE_BOARD_MEMBER) ?  Hedge::$statusLabelsRestricted : Hedge::$statusLabelsAll,
            'segments' => $segments,
            'subSegments' => $subSegments,
            'products' => $products,
            'maturities' => $maturities,
            'hedgingTools' => $hedgingTools,
            'operationsTypesLabels' => HedgingTool::$operationTypesLabels,
            'businessUnits' => $businessUnits,
            'selectedBusinessUnit' => $selectedBusinessUnit,
            'flagLabels' => Hedge::$flagLabels,
            'filters' => $filters,
            'route' => $route
        ]);
    }

    /**
     * @Route("/hedge/{hedge}/edit/{saved}", name="hedge_edit", methods={"GET", "POST"})
     * @Route(path="/hedge", name="hedge_create", methods={"GET", "POST"})
     *
     * @Security("is_granted(constant('\\App\\Security\\Voter\\HedgeVoter::HEDGE_EDIT'), hedge)")
     *
     * @param Request $request
     * @param Hedge|null $hedge
     * @param string $saved
     *
     * @return Response
     */
    public function editAction(Request $request, ?Hedge $hedge, string $saved = '', UomConverterManager $uomConverterManager): Response
    {
        $user  = $this->getUser();

        if (!$hedge instanceof Hedge) {
            $this->denyAccessUnlessGranted('hedge_create');

            $hedge = new Hedge();
            $hedge->addHedgeLine(new HedgeLine());
            $hedge->setCreator($user);
            $hedge->setStatus(Hedge::STATUS_DRAFT);
        }

        $firstMaturity = $this->getDoctrine()->getRepository(Maturity::class)->findFirstMaturity();

        if ($hedge->getRmp() && $hedge->getSubSegment()) {
            $rmpSubSegment = $this->getDoctrine()->getRepository(RmpSubSegment::class)->findOneBy(['rmp' => $hedge->getRmp(), 'subSegment' => $hedge->getSubSegment()]);
        }

        $disableAll = false;
        $subSegmentDeleted = false;
        if (isset($rmpSubSegment) && !$rmpSubSegment->isActive() && !$user->hasRole(User::ROLE_TRADER)
            || ($user->hasRole(User::ROLE_TRADER) && !$hedge->isPendingExecution() && isset($rmpSubSegment) && !$rmpSubSegment->isActive())) {
            $disableAll = true;
            $subSegmentDeleted = true;
        } else if ($user->hasRole(User::ROLE_TRADER) && $hedge->isPendingExecution() && isset($rmpSubSegment) && !$rmpSubSegment->isActive()) {
            $subSegmentDeleted = true;
        }

        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $user,
            'status' => $hedge->getStatus(),
            'disableAll' => $disableAll,
        ]);

        if ($hedge->isPendingApproval()) {
            $formRefuseComment = $this->get('form.factory')->createNamed('comment_refuse', CommentType::class, null,[
                'action' => $this->generateUrl('hedge_refuse', ['hedge' => $hedge->getId()]),
            ]);
            $formAcceptComment = $this->get('form.factory')->createNamed('comment_accept', CommentType::class, null,[
                'action' => $this->generateUrl('hedge_accept', ['hedge' => $hedge->getId()]),
            ]);
        }

        $comments = $this->getDoctrine()->getRepository(HedgeComment::class)->findBy(['parent' => $hedge], ['id' => 'ASC']);

        if ($hedge->getId() && (!$hedge->isDraft() || ($hedge->isDraft() && count($comments)))) {
            $formComment = $this->createForm(CommentType::class, null, [
                'action' => $this->generateUrl('hedge_comment_add', ['hedge' => $hedge->getId()])
            ]);
        }


        $hedgingTool = $hedge->getHedgingTool();
        if (!$hedgingTool instanceof HedgingTool) {
            $hedgingTool = $this->getDoctrine()->getRepository(HedgingTool::class)->findOneBy(['operationType' => HedgingTool::OPERATION_TYPE_BUY], ['name' => 'ASC']);
        }

        $form->handleRequest($request);
       
        $hedgeLinesError = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($hedge);
            $em->flush();

            return $this->redirectToRoute('hedge_edit', ['hedge' => $hedge->getId(), 'saved' => 'saved']);
        } elseif ($form->isSubmitted()) {
            $hedgeLinesError = true;
        }

        $totalRealized = $openExecutionVolume = 0;
        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            $totalRealized += $hedgeLine->getQuantityRealized();
        }

        if ($hedge->getTotalVolume()) {
            $openExecutionVolume = $hedge->getTotalVolume() - $totalRealized;
        }

        $logs = $this->getDoctrine()->getRepository(HedgeLog::class)->findBy(['parent' => $hedge], ['timestamp' => 'DESC']);

        return $this->render('hedge/form.html.twig', [
            'form' => $form->createView(),
            'firstMaturity' => $firstMaturity,
            'hedge' => $hedge,
            'totalRealized' => $totalRealized,
            'openExecutionVolume' => $openExecutionVolume,
            'hideSaveBtn' => (!empty($saved) && $user->hasRole(User::ROLE_TRADER) && $hedge->isPendingExecution()) || !$this->isGranted('hedge_save', $hedge),
            'hedgingTool' => $hedgingTool,
            'notPremiumHedgingTool' => HedgingTool::$notPremiumHedgingTool,
            'updateLines' => ($hedge->getStatus() == Hedge::STATUS_DRAFT) ? true : false,
            'updateWaivers' => $hedge->getStatus() == Hedge::STATUS_DRAFT ? 1 : 0,
            'formComment' => isset($formComment) ? $formComment->createView() : null,
            'formRefuseComment' => isset($formRefuseComment) ? $formRefuseComment->createView() : null,
            'formAcceptComment' => isset($formAcceptComment) ? $formAcceptComment->createView() : null,
            'comments' => $comments,
            'hedgeLinesError' => $hedgeLinesError,
            'subSegmentDeleted' => $subSegmentDeleted,
            'disableAll' => $disableAll,
            'logs' => $logs,
            'logsActionsLabels' => HedgeLog::$typeActionsLabels
        ]);
    }

    /**
     * @Route(path="/hedge/{hedge}/refuse", name="hedge_refuse", methods={"POST"})
     *
     * @IsGranted("hedge_validate", subject="hedge")
     * @param Request $request
     * @param Hedge $hedge
     * @param CommentManager $commentManager
     *
     * @return Response
     */
    public function refuseAction(Request $request, Hedge $hedge, CommentManager $commentManager): Response
    {
        $em = $this->getDoctrine()->getManager();

        $formComment = $this->get('form.factory')->createNamed('comment_refuse', CommentType::class, null,[
            'action' => $this->generateUrl('hedge_refuse', ['hedge' => $hedge->getId()]),
        ]);
        $formComment->handleRequest($request);
        $formData = $formComment->getData();
        if ($formData['message']) {
            $commentManager->createComment($hedge, $formData['message'], $this->getUser());
        }

        if ($hedge->getStatus() == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
            $hedge->setValidatorLevel1($this->getUser());
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_REJECTED_RISK_CONTROLLER, $formData['message']);
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_REJECTED_RISK_CONTROLLER,
                                                         $hedge,
                                                         [$hedge->getCreator()],
                                                        ['hedgeId' => $hedge->getId(),
                                                         'generalComment' => $formData['message'],
                                                         'url' => $this->generateUrl('hedge_edit',
                                                                                    ['hedge' => $hedge->getId()],
                                                                                    UrlGeneratorInterface::ABSOLUTE_URL)],
                                                        $formData['message']);
        } else {
            $hedge->setValidatorLevel2($this->getUser());
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_REJECTED_BOARD_MEMBER, $formData['message']);

            $riskControllers = $this->getDoctrine()->getRepository(User::class)->findByRole(User::ROLE_RISK_CONTROLLER);
            $users = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BU_MEMBER,
                                                                                                                  User::ROLE_BU_HEDGING_COMMITTEE],
                                                                                                                  $hedge->getRmp()->getBusinessUnit());

            $url = $this->generateUrl('hedge_edit', ['hedge' => $hedge->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_REJECTED_BOARD_MEMBER,
                                                         $hedge,
                                                         $users,
                                                        ['hedgeId' => $hedge->getId(),
                                                         'generalComment' => $formData['message'],
                                                         'url' => $url],
                                                        $formData['message']);
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_REJECTED_BOARD_MEMBER,
                                                         $hedge,
                                                         $riskControllers,
                                                        ['hedgeId' => $hedge->getId(),
                                                         'generalComment' => $formData['message'],
                                                         'url' => ''],
                                                        $formData['message']);
        }

        $hedge->setStatus(Hedge::STATUS_DRAFT);

        $em->persist($hedge);
        $em->flush();



        return $this->redirectToRoute('hedge_list');
    }

    /**
     * @Route(path="/hedge/{hedge}/accept", name="hedge_accept", methods={"POST"})
     *
     * @IsGranted("hedge_validate", subject="hedge")
     *
     * @param Request $request
     * @param Hedge $hedge
     * @param CommentManager $commentManager
     *
     * @return Response
     */
    public function acceptAction(Request $request, Hedge $hedge, CommentManager $commentManager): Response
    {
        $em = $this->getDoctrine()->getManager();

        $formComment = $this->get('form.factory')->createNamed('comment_accept', CommentType::class, null,[
            'action' => $this->generateUrl('hedge_accept', ['hedge' => $hedge->getId()]),
        ]);
        $formComment->handleRequest($request);
        $formData = $formComment->getData();
        if ($formData['message']) {
            $commentManager->createComment($hedge, $formData['message'], $this->getUser());
        }

        if ($hedge->getStatus() == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
            $hedge->setStatus(Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER);
            $hedge->setValidatorLevel1($this->getUser());
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_APPROVED_RISK_CONTROLLER, $formData['message']);

            $boardMembers = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $hedge->getRmp()->getBusinessUnit());
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_PENDING_APPROVAL_BOARD_MEMBER,
                                                        $hedge,
                                                        $boardMembers,
                                                        ['hedgeId' => $hedge->getId(),
                                                        'generalComment' => $formData['message'],
                                                        'buName' => $hedge->getRmp()->getBusinessUnit()->getFullName(),
                                                        'url' => $this->generateUrl('hedge_edit',
                                                                                    ['hedge' => $hedge->getId()],
                                                                                    UrlGeneratorInterface::ABSOLUTE_URL)],
                                                        $formData['message']);

        } else {
            $hedge->setStatus(Hedge::STATUS_PENDING_EXECUTION);
            $hedge->setExtraApproval(true);
            $hedge->setValidatorLevel2($this->getUser());
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_APPROVED_BOARD_MEMBER, $formData['message']);

            $buMembers = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BU_MEMBER, User::ROLE_BU_HEDGING_COMMITTEE], $hedge->getRmp()->getBusinessUnit());
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_EXTRA_APPROVAL_PENDING_EXECUTION,
                                                        $hedge,
                                                        $buMembers,
                                                        ['hedgeId' => $hedge->getId(),
                                                         'generalComment' => $formData['message'],
                                                         'url' => $this->generateUrl('hedge_edit',
                                                                                    ['hedge' => $hedge->getId()],
                                                                                    UrlGeneratorInterface::ABSOLUTE_URL)],
                                                        $formData['message']);
        }

        $em->persist($hedge);
        $em->flush();

        return $this->redirectToRoute('hedge_list');
    }

    /**
     * @Route(path="/hedge/{hedge}/generate_blotter", name="hedge_generate_blotter", methods={"GET"})
     *
     * @isGranted("hedge_generate_blotter", subject="hedge")
     *
     * @param Hedge $hedge
     * @param ExcelManager $excelManager
     *
     * @throws \Exception if the provided hedge is not pending execution.
     *
     * @return Response
     */
    public function generateBlotter(Hedge $hedge, ExcelManager $excelManager): Response
    {
        if ($hedge->isPendingExecution()) {
            $excelManager->setSheet(new ApoSheet($this->getDoctrine()->getManager(), $hedge, $this->getUser()));
            $excelManager->setSheet(new SwapSheet($this->getDoctrine()->getManager(), $hedge, $this->getUser()));
            $file = $excelManager->generateExcel();
            $fileName = date('Ymd') . '_' . $hedge->getId() . '_' .  str_replace(array('.', ' '), '_', $hedge->getRmp()->getBusinessUnit()->getFullName()) . '_Trade-blotter';

            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_BLOTTER_GENERATION);

            return new Response($file, Response::HTTP_OK, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'filename="'.$fileName.'.xls"',
            ]);

        } else {
            throw new \Exception('Bad hedge status');
        }
    }
}
