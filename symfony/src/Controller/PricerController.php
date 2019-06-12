<?php

namespace App\Controller;

use App\Entity\Pricer;
use App\Form\PricerType;
use App\Repository\PricerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PricerController
 * @package App\Controller
 */
class PricerController extends AbstractController
{
    /**
     * @Route(path="/pricer", name="pricer", methods={"GET","POST"})
     * @Security("is_granted('ROLE_USER')")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param PricerRepository $pricerRepository
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request, EntityManagerInterface $em, PricerRepository $pricerRepository): Response
    {
        $pricer = new Pricer();
        $form = $this->createForm(PricerType::class, $pricer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isGranted('ROLE_TRADER')) {

            // store all information to send as ajax response
            $output = [];

            $file = $request->files->get('file');
            $extension = $file->guessExtension();

            // check extension
            if (!in_array($extension, ['xls', 'xlsx', 'xlsm'])) {
                $output['uploaded'] = false;
                $output['message'] = 'modal.pricer.upload.error';
                return new JsonResponse($output);
            }

            // upload dir
            $uploadDir = sprintf(
                '%s/%s',
                $this->getParameter('kernel.project_dir'),
                $this->getParameter('pricer_files_dir')
            );

            // get filename and path
            if (!($file instanceof UploadedFile)) {
                $output['uploaded'] = false;
                $output['message'] = 'modal.pricer.upload.something_wrong';
                return new JsonResponse($output);
            }

            $filename = uniqid() . '.' . $extension;
            $originalFilePath = $uploadDir . '/' . $filename;

            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            // Move the file to the directory
            try {
                if ($file->move($uploadDir, $filename)) {
                    $output['uploaded'] = true;
                    $output['message'] = 'modal.pricer.upload.success';
                }
            } catch (FileException $e) {
                echo $e->getMessage();
            }

            // Save upload informations
            $pricer->setFilename($file->getClientOriginalName());
            $pricer->setUploadUser($this->getUser());
            $pricer->setFilePath($originalFilePath);

            $em->persist($pricer);
            $em->flush();

            return new JsonResponse($output);
        }

        return $this->render('pricer/index.html.twig', [
            'form' => $form->createView(),
            'pricer_files' => $pricerRepository->findAllInNumberOfDaysAgo(14)
        ]);
    }

    /**
     * @Route(path="/pricer/download/{pricer}", name="pricer_download", methods={"GET","POST"})
     * @Security("is_granted('ROLE_USER')")
     * @param Pricer $pricer
     * @return Response
     */
    public function downloadFile(Pricer $pricer)
    {
        if (!file_exists($pricer->getFilePath())) {
            throw $this->createException("This file doesn't exist");
        }

        return $this->file($pricer->getFilePath(), $pricer->getFilename());
    }
}
