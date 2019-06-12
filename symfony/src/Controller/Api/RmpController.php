<?php

namespace App\Controller\Api;

use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentComment;
use App\Service\LogManager;
use App\Service\RmpManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class RmpController extends Controller
{
    private $logManager;

    /**
     * HedgeController constructor.
     * @param LogManager $logManager
     */
    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    /**
     * @Route(path="/api/rmp/{rmp}/block/{block}", name="api_rmp_block")
     * @isGranted("rmp_block", subject="rmp")
     *
     * @param RMP $rmp
     * @param bool $block
     *
     * @return JsonResponse
     */
    public function blockRmpAction(RMP $rmp, bool $block)
    {
        $em = $this->getDoctrine()->getManager();

        $rmp->setBlocked($block);
        $em->persist($rmp);
        $em->flush();

        $this->logManager->createLog($rmp, $this->getUser(), RMPLog::TYPE_BLOCKED);

        return new JsonResponse(['data' => ['rmpId' => $rmp->getId()]]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/send_approval_request", name="api_rmp_approval_request")
     *
     * @isGranted("rmp_approval_request", subject="rmp")
     *
     * @param RMP $rmp
     * @return Response
     */
    public function rmpSendApprovalRequestAction(RMP $rmp)
    {
        $em = $this->getDoctrine()->getManager();

        $rmp->setStatus(RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER);
        $em->persist($rmp);
        $em->flush();

        $this->logManager->createLog($rmp, $this->getUser(), RMPLog::TYPE_AMENDMENT_REQUEST);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/cancel", name="api_cancel_rmp")
     *
     * @isGranted("rmp_cancel", subject="rmp")
     *
     * @param RMP $rmp
     * @return Response
     */
    public function rmpCancelAction(RMP $rmp)
    {
        $em = $this->getDoctrine()->getManager();
        $comments = $this->getDoctrine()->getRepository(RmpSubSegmentComment::class)->findByRmp($rmp);

        if (count($comments)) {
            $rmp->setStatus(RMP::STATUS_ARCHIVED);
            $em->persist($rmp);
            $this->logManager->createLog($rmp, $this->getUser(), RMPLog::TYPE_ARCHIVED);
        } else {
            $em->remove($rmp);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/is_blocked", name="api_is_blocked_rmp")
     *
     * @param RMP $rmp
     * @return Response
     */
    public function rmpIsBlockedAction(RMP $rmp)
    {
        return new JsonResponse(['blocked' => $rmp->isBlocked()]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/update_validity_period/{validityPeriod}", name="api_update_validity_period_rmp")
     *
     * @isGranted("rmp_edit", subject="rmp")
     *
     * @param RMP $rmp
     * @param int $validityPeriod
     *
     * @return Response
     */
    public function rmpUpdateValidityPeriod(RMP $rmp, int $validityPeriod)
    {
        $rmp->setName(str_replace($rmp->getValidityPeriod(), $validityPeriod, $rmp->getName()));
        $rmp->setValidityPeriod($validityPeriod);

        $em = $this->getDoctrine()->getManager();
        $em->persist($rmp);
        $em->flush();

        return new JsonResponse(['validityPeriod' => $validityPeriod, 'name' => $rmp->getName()]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/get_infos/{subSegment}", name="api_rmp_get_infos")
     *
     * @param RMP $rmp
     * @param SubSegment $subSegment
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    public function rmpGetInfosAction(RMP $rmp, SubSegment $subSegment, TranslatorInterface $translator)
    {
        $rmpSubSegments = $this->getDoctrine()->getRepository(RmpSubSegment::class)->findBy(['rmp' => $rmp, 'subSegment' => $subSegment, 'active' => true],
                                                                                                          ['version' => 'DESC']);

        $lastRmpSubSegment = $rmpSubSegments[0];

        return new JsonResponse(['maxClassRisk' => HedgingTool::$riskLevelsLabels[$lastRmpSubSegment->getMaximumRiskLevel()],
                                'maxMaturity' => $lastRmpSubSegment->getMaximumMaturities(),
                                'benchmark' => $lastRmpSubSegment->getProductsNamesAsArray(),
                                'maxLoss' => $lastRmpSubSegment->getMaximumLoss() > 0 ? $lastRmpSubSegment->getMaximumLoss() : $translator->trans(RMP::DEFAULT_MAX_LOSS)]);
    }
}