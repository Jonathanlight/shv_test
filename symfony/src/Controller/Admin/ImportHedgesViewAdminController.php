<?php

namespace App\Controller\Admin;

use App\Form\ImportType;
use App\Service\Import\HedgesImportManager;
use App\Service\ImportManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ImportHedgesViewAdminController extends AbstractController
{
    /**
     * @Route("/admin/import_hedges", name="import_hedges")
     *
     * @param Request $request
     * @param HedgesImportManager $hedgesImportManager
     *
     * @return Response
     */
    public function importAction(Request $request, HedgesImportManager $hedgesImportManager): Response
    {
        $form = $this->createForm(ImportType::class);

        $form->handleRequest($request);

        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $request->files->get('import')['file']['file'];

            $errors = $hedgesImportManager->importData($file->getRealPath());

            $form = $this->createForm(ImportType::class);
        }

        return $this->render('admin/import_hedges.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors
        ]);
    }
}
