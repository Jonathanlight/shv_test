<?php

namespace App\Controller\Api;

use App\Entity\MasterData\HedgingTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class HedgingToolController extends AbstractController
{
    /**
     * @Route(path="/api/operation_type/{operationType}/hedging_tools", name="api_get_hedging_tools", methods={"GET"})
     *
     * @param int $operationType
     *
     * @return JsonResponse
     */
    public function getHedgingToolsAction(int $operationType)
    {
        $hedgingToolsList = $this->getDoctrine()->getRepository(HedgingTool::class)->findByOperationType($operationType);

        return new JsonResponse(['data' => $hedgingToolsList]);
    }

    /**
     * @Route(path="/api/hedging_tool/{hedgingTool}/columns", name="api_get_hedging_tool_columns", methods={"GET"})
     *
     * @param HedgingTool $hedgingTool
     *
     * @return JsonResponse
     */
    public function getHedgingToolColumnsAction(HedgingTool $hedgingTool)
    {
        return new JsonResponse(['data' => $hedgingTool->getColumns()]);
    }

    /**
     * @Route(path="/api/hedging_tool_chart_image_url/{hedgingTool}", name="api_get_hedging_tool_chart_image_url", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     * @param HedgingTool $hedgingTool
     *
     * @return Response
     */
    public function getHedgingToolChartAction(HedgingTool $hedgingTool)
    {
        $filepath = $hedgingTool->getChartImagePath();

        if (!$filepath) {
            $filepath = $this->getParameter('kernel.project_dir') . '/public/img/image-not-found.png';
        }

        $filename = basename($filepath);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $response = new Response();
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/' . $extension);
        $response->setContent(file_get_contents($filepath));

        return $response;
    }
}
