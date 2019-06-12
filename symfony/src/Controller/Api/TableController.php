<?php

namespace App\Controller\Api;

use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\UOM;
use App\Service\ListFiltersManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TableController extends AbstractController
{
    /**
     * @Route(path="/table/sort", name="api_sort_table", methods={"POST"})
     *
     * @param Request $request
     * @param ListFiltersManager $listFiltersManager
     *
     * @return Response
     */
    public function sortTable(Request $request, ListFiltersManager $listFiltersManager)
    {
        $params = $request->request->all();
        $offset = ($params['page'] - 1) * $params['limit'];

        $entityRepository = $this->getDoctrine()->getRepository('App:'.$params['entity']);

        $orderBy = [];
        if (isset($params['field']) && isset($params['order'])) {
            $orderBy = [$params['field'] => $params['order']];
        }

        $results = $entityRepository->list($params['filters'], $orderBy, $params['limit'], $offset);

        $formattedFilters = $listFiltersManager->formatFilters($params['filters'], $params['page']);
        $listFiltersManager->setFilters($params['route'], $formattedFilters);

        $pagination = [
            'page' => $params['page'],
            'offset' => $offset,
            'nbPages' => ceil(count($results) / $params['limit']),
            'route' => 'api_sort_table',
            'params' => [],
        ];

        $referenceUom = $this->getDoctrine()->getRepository(UOM::class)->findOneByCode(UOM::BASE_UOM_CODE);

        $tableContent = $this->container->get('twig')->render('tables/'.strtolower($params['entity']).'_content.html.twig', [
            'results' => $results,
            'operationsTypesLabels' =>  HedgingTool::$operationTypesLabels,
            'referenceUom' => $referenceUom
        ]);
        $paginationContent = $this->container->get('twig')->render('tables/pagination.html.twig', [
            'pagination' => $pagination,
        ]);

        return new JsonResponse(['tableContent' => $tableContent, 'paginationContent' => $paginationContent]);
    }
}
