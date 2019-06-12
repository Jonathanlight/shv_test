<?php

namespace App\Controller\Api;

use App\Constant\Operations;
use App\Entity\CMS\Letter;
use App\Entity\Hedge;
use App\Entity\HedgeComment;
use App\Entity\HedgeLog;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Entity\User;
use App\Form\HedgeType;
use App\Service\Excel\ExcelManager;
use App\Service\Excel\SheetContents\ApoSheet;
use App\Service\HedgeLimitManager;
use App\Service\LogManager;
use App\Service\NotificationManager;
use App\Service\TradeManager;
use App\Service\TradeSimulator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @Route(path="/api/hedge/{hedge}/cancel", name="api_cancel_hedge", methods={"GET"})
     *
     * @IsGranted("hedge_cancel", subject="hedge")
     *
     * @param Hedge $hedge
     *
     * @return JsonResponse
     */
    public function cancelAction(Hedge $hedge): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if ($hedge->isDraft()) {
            $comments = $this->getDoctrine()->getRepository(HedgeComment::class)->findBy(['parent' => $hedge]);
            if (count($comments)) {
                $hedge->setStatus(Hedge::STATUS_CANCELED);
                $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_CANCELED);
                $buMembers = $this->em->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BU_MEMBER, User::ROLE_BU_HEDGING_COMMITTEE], $entity->getRmp()->getBusinessUnit());
                $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_CANCELLED,
                    $entity,
                    $buMembers,
                    ['hedgeId' => $entity->getId(),
                        'url' => $this->router->generate('hedge_edit',
                            ['hedge' => $entity->getId()],
                            UrlGeneratorInterface::ABSOLUTE_URL)]);
            } else {
                $em->remove($hedge);
            }
        } elseif ($hedge->isPendingExecution() && ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE))) {
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_CANCELLATION_REQUESTED);
            $hedge->setPendingCancelation(true);
            $em->persist($hedge);
        } else {
            $hedge->setStatus(Hedge::STATUS_CANCELED);
            $hedge->setCanceler($user);
            $em->persist($hedge);
            $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_CANCELED);
        }

        if ($hedge->getStatus() == Hedge::STATUS_CANCELED) {
            $buMembers = $this->getDoctrine()->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BU_MEMBER, User::ROLE_BU_HEDGING_COMMITTEE], $hedge->getRmp()->getBusinessUnit());
            $this->notificationManager->sendNotification(NotificationManager::TYPE_HEDGE_CANCELLED,
                                                        $hedge,
                                                        $buMembers,
                                                        ['hedgeId' => $hedge->getId(),
                                                        'url' => $this->generateUrl('hedge_edit',
                                                                                        ['hedge' => $hedge->getId()],
                                                                                        UrlGeneratorInterface::ABSOLUTE_URL)]);
        }

        $em->flush();

        return new JsonResponse(['hedgeId' => $hedge->getId(), 'pendingCancelation' => $hedge->isPendingCancelation()]);
    }

    /**
     * @Route(path="/api/hedge/{hedge}/write_off", name="api_write_off_hedge", methods={"GET"})
     *
     * @param Hedge $hedge
     *
     * @IsGranted("hedge_write_off", subject="hedge")
     *
     * @return JsonResponse
     */
    public function writeOffAction(Hedge $hedge): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $totalHedgeVolume = 0;
        $hedge->setStatus(Hedge::STATUS_REALIZED);
        $hedge->setPartiallyRealized(false);

        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            if ($hedgeLine->getQuantity() > $hedgeLine->getQuantityRealized()) {
                $hedgeLine->setQuantityCanceled($hedgeLine->getQuantity() - $hedgeLine->getQuantityRealized());
                $hedgeLine->setQuantity($hedgeLine->getQuantityRealized());
            }
            $totalHedgeVolume += $hedgeLine->getQuantityRealized();
            $em->persist($hedgeLine);
        }

        $hedge->setTotalVolume($totalHedgeVolume);
        $em->persist($hedge);
        $em->flush();

        $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_WRITE_OFF);

        return new JsonResponse(['hedgeId' => $hedge->getId()]);
    }

    /**
     * @Route(path="/api/hedge/volumes_limits", name="api_get_volumes_limits", methods={"POST"})
     *
     * @param Request $request
     * @param HedgeLimitManager $hedgeLimitManager
     *
     * @return JsonResponse
     */
    public function getVolumesAndLimits(Request $request, HedgeLimitManager $hedgeLimitManager): JsonResponse
    {
        $hedge = new Hedge();
        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $this->getUser(),
            'status' => $hedge->getStatus(),
            'loadAllRmps' => true,
        ]);
        $form->handleRequest($request);

        return new JsonResponse($hedgeLimitManager->getVolumeAndLimitDetails($hedge));
    }

    /**
     * @Route("/api/hedge/execution_request", name="api_hedge_create_execution_request", methods={"POST"})
     * @Route("/api/hedge/{hedge}/execution_request", name="api_hedge_execution_request", methods={"POST"})
     *
     * @param Request $request
     * @param Hedge $hedge
     * @param HedgeLimitManager $hedgeLimitManager
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function executionRequestAction(Request $request, ?Hedge $hedge, HedgeLimitManager $hedgeLimitManager, TranslatorInterface $translator): JsonResponse
    {
        if (!$hedge instanceof Hedge) {
            $hedge = new Hedge();
            $hedge->setCreator($this->getUser());
            $hedge->setStatus(Hedge::STATUS_DRAFT);
        }

        $this->denyAccessUnlessGranted('hedge_request_execution', $hedge);

        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $this->getUser(),
            'status' => $hedge->getStatus()
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if (($hedge->getOperationType() == Operations::OPERATION_TYPE_BUY && $hedgeLimitManager->isWaiver($hedge))
                || ($hedge->getOperationType() == Operations::OPERATION_TYPE_SELL && $hedgeLimitManager->isSellExtraApproval($hedge))) {
                if ($hedgeLimitManager->isWaiverProduct($hedge)) {
                    $hedge->setWaiverProduct(true);
                }
                if ($hedgeLimitManager->isWaiverClassRiskLevel($hedge)) {
                    $hedge->setWaiverClassRiskLevel(true);
                }
                foreach ($hedge->getHedgeLines() as $hedgeLine) {
                    if ($hedgeLimitManager->isWaiverMaturity($hedgeLine)) {
                        $hedgeLine->setWaiverMaturity(true);
                    }
                    if ($hedgeLimitManager->isWaiverVolume($hedgeLine)) {
                        $hedgeLine->setWaiverVolume(true);
                    }
                }
                $hedge->setStatus(Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER);
            } else {
                $hedge->setStatus(Hedge::STATUS_PENDING_EXECUTION);
            }

            $hedge->setFirstRmp($hedge->getRmp());

            $em->persist($hedge);
            $em->flush();

            if ($hedge->isPendingExecution()) {
                $this->logManager->createLog($hedge, $this->getUser(), HedgeLog::TYPE_PENDING_EXECUTION);
            } else {
                $this->logManager->createLog($hedge,  $this->getUser(), HedgeLog::TYPE_EXTRA_APPROVAL);
            }

            $response['hedgeId'] = $hedge->getId();
        } else {
            $response['error'] = $translator->trans('hedge.hedge_lines.error');
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/hedge/check_sell_extra_approval", name="api_hedge_check_sell_extra_approval", methods={"POST"})
     *
     * @param Request $request
     * @param HedgeLimitManager $hedgeLimitManager
     * @return JsonResponse
     */
    public function checkSellExtraApproval(Request $request, HedgeLimitManager $hedgeLimitManager): JsonResponse
    {
        $hedge = new Hedge();
        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $this->getUser(),
            'status' => Hedge::STATUS_DRAFT
        ]);
        $form->handleRequest($request);

        $isExtraApproval = $hedgeLimitManager->isSellExtraApproval($hedge);

        return new JsonResponse(['isExtraApproval' => $isExtraApproval]);
    }

    /**
     * @Route("/api/hedge/check_sell_volume", name="api_hedge_check_sell_volume", methods={"POST"})
     *
     * @param Request $request
     * @param HedgeLimitManager $hedgeLimitManager
     *
     * @return JsonResponse
     */
    public function checkSellVolume(Request $request, HedgeLimitManager $hedgeLimitManager): JsonResponse
    {
        $hedge = new Hedge();
        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $this->getUser(),
            'status' => Hedge::STATUS_DRAFT
        ]);
        $form->handleRequest($request);

        $isSellExtraVolume = $hedgeLimitManager->isSellExtraVolume($hedge);

        return new JsonResponse(['isSellExtraVolume' => $isSellExtraVolume]);
    }

    /**
     * @Route("/api/hedge/{hedge}/test_generator/{type}", name="api_hedge_test_generator", methods={"GET"})
     *
     * @IsGranted("hedge_test_generator", subject="hedge")
     *
     * @param Hedge $hedge
     * @param TradeSimulator $tradeSimulator
     * @param TradeManager $tradeManager
     * @return JsonResponse
     */
    public function testGeneratorAction(Hedge $hedge, string $type, TradeSimulator $tradeSimulator, TradeManager $tradeManager): JsonResponse
    {
        $trades = $tradeSimulator->generateTradesFromHedge($hedge, $type);
        $tradeManager->processTrades($trades);

        return new JsonResponse(['hedgeId' => $hedge->getId()]);
    }

    /**
     * @Route(path="/api/hedge/{hedge}/modal_content", name="api_hedge_comment_modal_content")
     *
     * @param Hedge $hedge
     * @return Response
     */
    public function modalContentAction(Hedge $hedge): Response
    {
        return $this->forward('App\Controller\Api\CommentController::modalContentAction', ['parent' => $hedge, 'pathClass' => get_class($hedge)]);
    }

    /**
     * @Route(path="/api/hedge/{hedge}/comment", name="hedge_comment_add")
     *
     * @param Hedge $hedge
     * @return Response
     */
    public function commentAction(Hedge $hedge): Response
    {
        return $this->forward('App\Controller\Api\CommentController::commentAction', ['parent' => $hedge]);
    }

    /**
     * @Route("/api/hedge/save_new", name="api_hedge_save_new", methods={"POST"})
     * @Route("/api/hedge/{hedge}/save", name="api_hedge_save", methods={"POST"})
     *
     * @param Request $request
     * @param Hedge $hedge
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    public function saveAction(Request $request, ?Hedge $hedge, TranslatorInterface $translator): Response
    {
        if (!$hedge instanceof Hedge) {
            $hedge = new Hedge();
            $hedge->setCreator($this->getUser());
            $hedge->setStatus(Hedge::STATUS_DRAFT);
        }

        $this->denyAccessUnlessGranted('hedge_save', $hedge);

        $form = $this->createForm(HedgeType::class, $hedge, [
            'selectedBusinessUnit' => $request->get('selectedBusinessUnit'),
            'user' => $this->getUser(),
            'status' => $hedge->getStatus()
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($hedge);
            $em->flush();

            $response['hedgeId'] = $hedge->getId();
        } else {
            $response['error'] = $translator->trans('hedge.hedge_lines.error');
        }

        return new JsonResponse($response);
    }
}
