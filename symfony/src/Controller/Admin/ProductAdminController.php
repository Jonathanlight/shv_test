<?php

namespace App\Controller\Admin;

use App\Entity\MasterData\Product;
use App\Service\Api\CXLClientService;
use App\Service\ImportManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Response;

class ProductAdminController extends CRUDController
{
    /**
     * @param ImportManager $importManager
     * @param CXLClientService $cXLClientService
     * @return Response
     */
    public function importAction(ImportManager $importManager, CXLClientService $cXLClientService): Response
    {
        $values = $cXLClientService->getProducts();

        $importManager->import(Product::class, Product::$importXMLColsMapping,
                               Product::IMPORT_IDENTIFIER_INDEX, $values);

        $this->addFlash('sonata_flash_success', 'Products imported successfully');

        return $this->redirectToRoute('sonata_admin_dashboard');
    }
}
