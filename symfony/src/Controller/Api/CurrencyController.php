<?php

namespace App\Controller\Api;

use App\Entity\MasterData\Currency;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{
    /**
     * @Route(path="/api/currency/{currency}/get_infos", name="api_currency_get_infos", methods={"GET"})
     *
     * @param Currency $currency
     *
     * @return JsonResponse
     */
    public function getInfosAction(Currency $currency)
    {
        return new JsonResponse(['currencyId' => $currency->getId(), 'name' => $currency->getName(), 'currencyCode' => $currency->getCode()]);
    }
}
