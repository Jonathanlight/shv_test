<?php

namespace App\Controller\Api;

use App\Entity\HedgeAlertUser;
use App\Entity\RmpAlertUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class AlertController extends AbstractController
{
    /**
     * @Route(path="/api/alerts/{type}/{alertIds}", name="api_view_alerts", methods={"GET"})
     *
     * @param string $type
     * @param string $alertIds
     *
     * @return JsonResponse
     */
    public function viewAlertsAction(string $type, string $alertIds = ''): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $idsAsArray = explode(',', $alertIds);

        $parentClass = 'App\Entity\\' . $type;

        if (class_exists($parentClass)) {
            $alerts = $this->getDoctrine()->getRepository('App:'. ucfirst(strtolower($type)).'AlertUser')->findBy(['id' => $idsAsArray]);

            foreach ($alerts as $alert) {
                $alert->setViewed(true);
                $em->persist($alert);
            }

            $em->flush();

            $response = ['success' => true, 'nbViewed' => count($alerts), 'type' => strtolower($type)];
        } else {
            $response = ['success' => false];
        }

        return new JsonResponse($response);
    }

    /**
     * @Route(path="/api/alert/{id}/{type}/read", name="api_read_alert", methods={"GET"})
     *
     * @param int $id
     * @param string $type
     *
     * @return JsonResponse
     */
    public function readAlertAction(int $id, string $type): JsonResponse
    {
        $alert = $this->getDoctrine()->getRepository('App:'.ucfirst($type).'AlertUser')->find($id);
        $this->denyAccessUnlessGranted('alert_read', $alert);

        if ($alert instanceof HedgeAlertUser || $alert instanceof RmpAlertUser) {
            $em = $this->getDoctrine()->getManager();

            $alert->setIsRead(true);
            $em->persist($alert);
            $em->flush();
        }

        return new JsonResponse(['alertId' => $alert->getId()]);
    }

    /**
     * @Route(path="/api/alert/{id}/{type}/delete", name="api_delete_alert", methods={"GET"})
     * @param int $id
     * @param string $type
     * @return JsonResponse
     */
    public function deleteAlertAction(int $id, string $type): JsonResponse
    {
        $alert = $this->getDoctrine()->getRepository('App:'.ucfirst($type).'AlertUser')->find($id);
        $this->denyAccessUnlessGranted('alert_delete', $alert);

        if ($alert instanceof HedgeAlertUser || $alert instanceof RmpAlertUser) {
            $em = $this->getDoctrine()->getManager();

            $alert->setDeleted(true);
            $em->persist($alert);
            $em->flush();
        }

        return new JsonResponse(['alertId' => $alert->getId()]);
    }
}