<?php

namespace App\Controller\Api;

use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PriceRiskClassificationController extends AbstractController
{
    /**
     * @Route(path="/api/rmp/{rmp}/sub_segment/{subSegment}/price_risk_classification", name="api_get_price_risk_classification", methods={"GET"})
     *
     * @param RMP        $rmp
     * @param SubSegment $subSegment
     *
     * @return JsonResponse
     */
    public function getSegmentsAction(RMP $rmp, SubSegment $subSegment)
    {
        $selectedRmpSubSegment = $this->getDoctrine()->getRepository(RmpSubSegment::class)->findOneBy(['subSegment' => $subSegment, 'rmp' => $rmp]);

        return new JsonResponse(['data' => $selectedRmpSubSegment->getPriceRiskClassification()->getId()]);
    }
}
