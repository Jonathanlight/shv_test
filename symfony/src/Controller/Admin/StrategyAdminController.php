<?php

namespace App\Controller\Admin;

use App\Entity\MasterData\Product;
use App\Entity\MasterData\Strategy;
use App\Service\Api\CXLClientService;
use App\Service\ImportManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StrategyAdminController extends CRUDController
{
    /**
     * @param ImportManager $importManager
     * @param CXLClientService $cXLClientService
     * @return Response
     */
    public function importAction(ImportManager $importManager, CXLClientService $cXLClientService): Response
    {
        $values = $cXLClientService->getStrategies();

        $importManager->import(Strategy::class, Strategy::$importXMLColsMapping,
                        Strategy::IMPORT_IDENTIFIER_INDEX, $values);

        $this->addFlash('sonata_flash_success', 'Strategies imported successfully');

        return $this->redirectToRoute('sonata_admin_dashboard');
    }
}
