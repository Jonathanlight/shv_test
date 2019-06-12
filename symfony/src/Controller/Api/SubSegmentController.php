<?php

namespace App\Controller\Api;

use App\Entity\MasterData\Segment;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SubSegmentController extends AbstractController
{
    /**
     * @Route(path="/api/rmp/{rmp}/segment/{segment}/sub_segments", name="api_get_sub_segments_by_rmp", methods={"GET"})
     *
     * @param RMP     $rmp
     * @param Segment $segment
     *
     * @return JsonResponse
     */
    public function getSubSegmentsByRmpAction(RMP $rmp, Segment $segment)
    {
        $subSegmentsList = $this->getDoctrine()->getRepository(SubSegment::class)->findByRmpAndSegmentAsArray($rmp, $segment);

        return new JsonResponse(['data' => $subSegmentsList]);
    }

    /**
     * @Route(path="/api/rmp/{rmp}/segment/{segment}/sub_segments_not_used/{currentRmpSubSegment}", name="api_get_sub_segments_by_segment", methods={"GET"})
     *
     * @param RMP $rmp
     * @param Segment $segment
     * @param RmpSubSegment|null $currentRmpSubSegment
     *
     * @return JsonResponse
     */
    public function getSubSegmentsBySegment(RMP $rmp, Segment $segment, ?RmpSubSegment $currentRmpSubSegment)
    {
        $subSegments = $this->getDoctrine()->getRepository(SubSegment::class)->findBy(['segment' => $segment, 'active' => 1]);

        $subSegmentsUsed = [];

        foreach ($rmp->getActiveRmpSubSegments() as $rmpSubSegment) {
            if ($currentRmpSubSegment instanceof RmpSubSegment && $currentRmpSubSegment->getId() == $rmpSubSegment->getId()) {
                continue;
            }
            $subSegmentsUsed[] = $rmpSubSegment->getSubSegment()->getId();
        }

        $subSegmentsList = [];
        foreach ($subSegments as $subSegment) {
            $subSegmentsList[] = ['id' => $subSegment->getId(), 'name' => $subSegment->getName(), 'disabled' => (int)in_array($subSegment->getId(), $subSegmentsUsed)];
        }

        return new JsonResponse(['data' => $subSegmentsList]);
    }
}
