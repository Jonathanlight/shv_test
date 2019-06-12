<?php

namespace App\Controller\Api;

use App\Entity\MasterData\BusinessUnit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route(path="/selected_business_unit/{businessUnit}/update", name="api_update_selected_bu", methods={"GET"})
     *
     * @param BusinessUnit $businessUnit
     *
     * @return Response
     */
    public function updateSelectedBusinessUnit(BusinessUnit $businessUnit)
    {
        $session = $this->get('session');
        $session->set('selectedBusinessUnit', $businessUnit);

        return new JsonResponse();
    }
}
