<?php

namespace App\Controller\Api;

use App\Entity\Field;
use App\Entity\Pricer;
use App\Form\FieldType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

class PricerController extends AbstractController
{
    /**
     * @Route(path="/api/pricer/update_dashboard", name="api_pricer_update_dashboard", methods={"POST"})
     *
     * @Security("is_granted('ROLE_TRADER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDashboardInfos(Request $request): JsonResponse
    {
        $fieldRepository = $this->getDoctrine()->getRepository(Field::class);
        $em = $this->getDoctrine()->getManager();

        foreach (Field::$pricerFields as $fieldCode) {
            $field = $fieldRepository->findOneByCode($fieldCode);
            if (!$field instanceof Field) {
                $field = new Field();
                $field->setCode($fieldCode);
            }

            $field->setUser($this->getUser());

            $form = $this->container->get('form.factory')->createNamed(str_replace('.', '_', $fieldCode), FieldType::class, $field);
            $pricerInfoForms[] = $form;
            $fields[] = $field;
        }

        foreach ($pricerInfoForms as $form) {
            $form->handleRequest($request);
        }

        foreach ($fields as $field) {
            $em->persist($field);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route(path="/api/pricer/delete_file/{pricer}", name="api_pricer_delete_file", methods={"GET","POST"})
     * @Security("is_granted('ROLE_TRADER')")
     * @param Pricer $pricer
     * @return JsonResponse
     */
    public function deleteFile(Pricer $pricer, EntityManagerInterface $entityManager)
    {
        if (!file_exists($pricer->getFilePath())) {
            return new JsonResponse([
                'deleted' => false,
                'message' => 'modal.pricer.delete.error'
            ]);
        }

        // delete physical file
        if (unlink($pricer->getFilePath())) {
            // delete file in database
            $entityManager->remove($pricer);
            $entityManager->flush();

            return new JsonResponse([
                'deleted' => true,
                'message' => 'modal.pricer.delete.success'
            ]);
        }

        return new JsonResponse([
            'deleted' => false,
            'message' => 'modal.pricer.delete.something_wrong'
        ]);
    }
}
