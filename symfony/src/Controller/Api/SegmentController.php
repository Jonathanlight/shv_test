<?php

namespace App\Controller\Api;

use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SegmentController extends AbstractController
{
    /**
     * @Route(path="/api/rmp/{rmp}/segments", name="api_get_segments", methods={"GET"})
     *
     * @param RMP $rmp
     *
     * @return JsonResponse
     */
    public function getSegmentsAction(RMP $rmp)
    {
        $segmentsList = $this->getDoctrine()->getRepository(Segment::class)->findByRmpAsFormattedArray($rmp);

        return new JsonResponse(['data' => $segmentsList]);
    }

    /**
     * @Route(path="/api/sub_segment/{subSegment}/segment", name="api_get_segment_by_sub_segment", methods={"GET"})
     *
     * @param SubSegment $subSegment
     *
     * @return JsonResponse
     */
    public function getSegmentBySubSegment(SubSegment $subSegment)
    {
        return new JsonResponse(['data' => ['segmentId' => $subSegment->getSegment()->getId()]]);
    }
}
