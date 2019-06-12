<?php

namespace App\Controller;

use App\Entity\MasterData\HedgingTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HedgingToolController extends AbstractController
{
    /**
     * @Route(path="/hedging_tools", name="hedging_tools", methods={"GET"})
     *
     * @return Response
     */
    public function hedgingToolsListAction(): Response
    {
        $hedgingToolRepository = $this->getDoctrine()->getRepository(HedgingTool::class);

        $buyHedgingTools = $hedgingToolRepository->findBy(['active' => 1, 'operationType' => HedgingTool::OPERATION_TYPE_BUY]);
        $sellHedgingTools = $hedgingToolRepository->findBy(['active' => 1, 'operationType' => HedgingTool::OPERATION_TYPE_SELL]);

        return $this->render('hedging_tools/index.html.twig', [
            'riskLevelLabels' => HedgingTool::$riskLevelsLabels,
            'buyHedgingTools' => $buyHedgingTools,
            'sellHedgingTools' => $sellHedgingTools,
        ]);
    }
}
