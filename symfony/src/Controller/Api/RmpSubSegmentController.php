<?php

namespace App\Controller\Api;

use App\Entity\Hedge;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\Segment;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentRiskLevel;
use App\Form\RmpSubSegmentRiskLevelType;
use App\Form\RmpSubSegmentType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RmpSubSegmentController extends AbstractController
{
    /**
     * @Route(path="/api/{rmp}/rmp_sub_segments", name="api_get_rmp_sub_segments", methods={"POST"})
     *
     * @param Request $request
     * @param RMP $rmp
     *
     * @return JsonResponse
     */
    public function getRmpSubSegmentsByMaturitiesAction(Request $request, RMP $rmp): JsonResponse
    {
        $params = $request->request->all();
        $businessUnit = $rmp->getBusinessUnit();

        if (isset($params['data'])) {
            $maturitiesId = [];
            $subSegmentId = 0;

            foreach ($params['data'] as $datum) {
                $maturitiesId[] = $datum['maturityId'];
                $subSegmentId = $datum['subSegmentId'];
            }

            $maturities = $this->getDoctrine()->getRepository(Maturity::class)->findBy(array('id' => $maturitiesId));

            $maturitiesYear = [];
            foreach ($maturities as $maturity) {
                if (!in_array($maturity->getYear(), $maturitiesYear)) {
                    $maturitiesYear[] = $maturity->getYear();
                }
            }

            $rmps = $this->getDoctrine()->getRepository(RMP::class)->findByValidityPeriodsAndBusinessUnit($maturitiesYear, $businessUnit);

            $rmpSubSegments = $this->getDoctrine()->getRepository(RmpSubSegment::class)->findBy(array('rmp' => $rmps, 'subSegment' => $subSegmentId));

            $rmpSubSegmentsIds = [];
            foreach ($rmpSubSegments as $rmpSubSegment) {
                $rmpSubSegmentsIds[$rmpSubSegment->getRmp()->getValidityPeriod()] = $rmpSubSegment->getId();
            }

            return new JsonResponse(['data' => $rmpSubSegmentsIds]);
        }

        return new JsonResponse();
    }

    /**
     * @Route(path="/api/rmp_sub_segment/{rmpSubSegment}/modal_content", name="api_rmp_sub_segment_comment_modal_content")
     *
     * @param RmpSubSegment $rmpSubSegment
     * @return Response
     */
    public function modalContentAction(RmpSubSegment $rmpSubSegment): JsonResponse
    {
        return $this->forward('App\Controller\Api\CommentController::modalContentAction', ['parent' => $rmpSubSegment, 'pathClass' => get_class($rmpSubSegment)]);
    }

    /**
     * @Route(path="/api/rmp_sub_segment/{rmpSubSegment}/comment", name="rmpSubSegment_comment_add")
     *
     * @param RmpSubSegment $rmpSubSegment
     * @return Response
     */
    public function commentAction(RmpSubSegment $rmpSubSegment): Response
    {
        return $this->forward('App\Controller\Api\CommentController::commentAction', ['parent' => $rmpSubSegment]);
    }

    /**
     *
     * @Route("/api/{rmp}/rmp_sub_segment/{rmpSubSegment}/modal_edit_content", name="api_rmp_sub_segment_edit_modal_content")
     *
     * @param RMP $rmp
     * @param RmpSubSegment $rmpSubSegment
     *
     * @return Response
     */
    public function modalEditContentAction(RMP $rmp, RmpSubSegment $rmpSubSegment): Response
    {
        $rmpSubSegmentRiskLevels = $this->getDoctrine()->getRepository(RmpSubSegmentRiskLevel::class)->findBy(['rmpSubSegment' => $rmpSubSegment]);

        return $this->forward('App\Controller\Api\RmpSubSegmentController::rmpSubSegmentModalContent', ['rmp' => $rmp, 'rmpSubSegment' => $rmpSubSegment, 'rmpSubSegmentRiskLevels' => $rmpSubSegmentRiskLevels]);
    }

    /**
     * @Route("/api/{rmp}/rmp_sub_segment/{segment}/modal_create_content", name="api_rmp_sub_segment_create_modal_content")
     *
     * @param RMP $rmp
     * @param Segment|null $segment
     * @return Response
     */
    public function modalCreateContentAction(RMP $rmp, ?Segment $segment): Response
    {
        $rmpSubSegment = new RmpSubSegment();

        $rmpSubSegmentRiskLevels = [];

        for ($i = 0; $i < 5; $i++) {
            $rmpSubSegmentRiskLevel = new RmpSubSegmentRiskLevel();
            $rmpSubSegmentRiskLevel->setRiskLevel($i);
            $rmpSubSegmentRiskLevels[] = $rmpSubSegmentRiskLevel;
        }

        return $this->forward('App\Controller\Api\RmpSubSegmentController::rmpSubSegmentModalContent',
                                ['rmp' => $rmp,
                                 'rmpSubSegment' => $rmpSubSegment,
                                 'rmpSubSegmentRiskLevels' => $rmpSubSegmentRiskLevels,
                                 'segment' => $segment]);
    }

    /**
     * @param RMP $rmp
     * @param RmpSubSegment $rmpSubSegment
     * @param array $rmpSubSegmentRiskLevels
     * @param Segment|null $segment
     * @return JsonResponse
     */
    public function rmpSubSegmentModalContent(RMP $rmp, RmpSubSegment $rmpSubSegment, array $rmpSubSegmentRiskLevels, ?Segment $segment)
    {
        if ($rmpSubSegment->getId()) {
            $action = $this->generateUrl('rmp_edit_rmp_sub_segment', ['rmp' => $rmp->getId(), 'rmpSubSegment' => $rmpSubSegment->getId()]);
        } else {
            $action = $this->generateUrl('rmp_create_rmp_sub_segment', ['rmp' => $rmp->getId()]);
        }

        $rmpSubSegmentForm = $this->createForm(RmpSubSegmentType::class, $rmpSubSegment, [
            'action' => $action,
            'activeRmpSubSegments' => $rmp->getActiveRmpSubSegments()
        ]);

        $selectedRiskLevel = 1;
        if ($rmpSubSegment->getId() && $rmpSubSegment->getRmpSubSegmentRiskLevelByRiskLevel(HedgingTool::RISK_LEVEL_0)->getMaximumVolume()) {
            $selectedRiskLevel = 0;
        }

        $modalParams = [
            'rmpSubSegmentForm' => $rmpSubSegmentForm->createView(),
            'rmp' => $rmp,
            'rmpSubSegment' => $rmpSubSegment,
            'segment' => $segment,
            'create' => $rmpSubSegment->getId() ? false : true,
            'selectedRiskLevel' => $selectedRiskLevel,
        ];

        foreach ($rmpSubSegmentRiskLevels as $rmpSubSegmentRiskLevel) {
            $rmpSubSegmentRiskForm = $this->get('form.factory')->createNamed('rmp_sub_segment_risk_level_'.$rmpSubSegmentRiskLevel->getRiskLevel(), RmpSubSegmentRiskLevelType::class, $rmpSubSegmentRiskLevel);
            $modalParams['rmpSubSegmentRiskForm'.$rmpSubSegmentRiskLevel->getRiskLevel()] = $rmpSubSegmentRiskForm->createView();
            $rmpSubSegmentRiskLevels[] = $rmpSubSegmentRiskLevel;
        }

        $response['content'] = $this->container->get('twig')->render('rmp/modal_rmp_sub_segment_content.html.twig', $modalParams);

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/rmp_sub_segment/{rmpSubSegment}/remove", name="api_rmp_sub_segment_remove")
     *
     * @param RmpSubSegment $rmpSubSegment
     * @return Response
     */
    public function removeRmpSubSegment(RmpSubSegment $rmpSubSegment): Response
    {
        $this->denyAccessUnlessGranted('rmp_edit', $rmpSubSegment->getRmp());

        $em = $this->getDoctrine()->getManager();
        $rmpSubSegment->setActive(false);
        $em->persist($rmpSubSegment);
        $em->flush();

        return new JsonResponse(['rmpId' => $rmpSubSegment->getRmp()->getId()]);
    }
}