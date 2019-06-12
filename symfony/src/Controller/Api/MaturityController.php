<?php

namespace App\Controller\Api;

use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MaturityController extends AbstractController
{
    /**
     * @Route(path="/api/rmp/{rmp}/maturities", name="api_get_maturities_by_rmp", methods={"GET"})
     *
     * @param RMP $rmp
     *
     * @return JsonResponse
     */
    public function getMaturitiesByRmpAction(RMP $rmp)
    {
        $maturities = $this->getDoctrine()->getRepository(Maturity::class)->findByRmpAndBusinessUnit($rmp, $rmp->getBusinessUnit());

        return new JsonResponse(['data' => $maturities]);
    }

    /**
     * @Route(path="/api/{rmp}/maturity/{maturity}/maturities", name="api_get_maturities_by_maturity", methods={"GET"})
     *
     * @param Maturity $maturity
     * @param  RMP $rmp
     *
     * @return JsonResponse
     */
    public function getMaturitiesByMaturityAction(Maturity $maturity, RMP $rmp)
    {
        $maturities = $this->getDoctrine()->getRepository(Maturity::class)->findByMaturityAndBusinessUnit($maturity, $rmp->getBusinessUnit(), $rmp);

        return new JsonResponse(['data' => $maturities]);
    }

    /**
     * @Route(path="/api/{rmp}/first_maturity/{firstMaturity}/last_maturity/{lastMaturity}/maturities_range/{subSegment}", name="api_get_maturities_range", methods={"GET"})
     *
     * @param RMP $rmp
     * @param Maturity $firstMaturity
     * @param Maturity $lastMaturity
     * @param SubSegment $subSegment
     *
     * @return JsonResponse
     */
    public function getMaturitiesRange(RMP $rmp, Maturity $firstMaturity, Maturity $lastMaturity, SubSegment $subSegment)
    {
        $maturities = $this->getDoctrine()->getRepository(Maturity::class)->findMaturitiesRangeWithRmpSubSegment($firstMaturity, $lastMaturity, $rmp->getBusinessUnit(), $subSegment, $rmp);

        return new JsonResponse(['data' => $maturities]);
    }
}
