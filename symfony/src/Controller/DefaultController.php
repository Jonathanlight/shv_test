<?php

namespace App\Controller;

use App\Entity\Hedge;
use App\Entity\HedgeAlert;
use App\Entity\HedgeAlertUser;
use App\Entity\RMP;
use App\Entity\RmpAlert;
use App\Entity\RmpAlertUser;
use App\Entity\RmpSubSegmentComment;
use App\Form\CommentType;
use App\Service\DashboardManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route(path="/", name="homepage", methods={"GET"})
     *
     * @param Request $request
     * @param DashboardManager $dashboardManager
     * @return Response
     */
    public function index(Request $request, DashboardManager $dashboardManager): Response
    {
        $dashboardStats = $dashboardManager->getStatsByUser($this->getUser());

        $currentRmp = null;
        if ($this->getUser()->isBuHedgingCommittee()) {
            $currentRmp = $this->getDoctrine()->getRepository(RMP::class)->findFirstByBusinessUnits([$request->get('selectedBusinessUnit')]);
        }

        return $this->render('default/index.html.twig', [
            'stats' => $dashboardStats,
            'currentRmp' => $currentRmp,
            'pricingEmail' => getenv('PRICING_EMAIL')
        ]);
    }

    /**
     * @Route(path="/analysis", name="analysis", methods={"GET"})
     *
     * @return Response
     */
    public function analysis(): Response
    {
        return $this->render('default/analysis.html.twig');
    }

    /**
     * @return Response
     */
    public function alertListAction(): Response
    {
        $alerts = $this->getAlerts();

        return $this->render('common/alerts.html.twig', [
            'hedgeAlertsUser' => $alerts['hedgeAlerts']['alerts'],
            'hedgeAlertsNotViewedCount' => $alerts['hedgeAlerts']['notViewedCount'],
            'hedgeAlertsLabels' =>  HedgeAlert::$typeLabels,
            'rmpAlertsUser' => $alerts['rmpAlerts']['alerts'],
            'rmpAlertsNotViewedCount' => $alerts['rmpAlerts']['notViewedCount'],
            'rmpAlertsLabels' =>  RmpAlert::$typeLabels,
            'hedgeStatusLabels' => Hedge::$statusLabelsAll
        ]);
    }

    public function headerAction(): Response
    {
        $alerts = $this->getAlerts();

        return $this->render('common/header.html.twig', [
            'alertsCount' => $alerts['hedgeAlerts']['notViewedCount'] + $alerts['rmpAlerts']['notViewedCount'],
            'dev' => getenv('APP_ENV') == 'dev',
        ]);
    }

    private function getAlerts(): array
    {
        $hedgeAlertsNotViewed = $rmpAlertsNotViewed = 0;
        $hedgeAlerts = $this->getDoctrine()->getRepository(HedgeAlertUser::class)->findByUserOrderedByTimestamp($this->getUser());
        $rmpAlerts = $this->getDoctrine()->getRepository(RmpAlertUser::class)->findByUserOrderedByTimestamp($this->getUser());

        foreach ($hedgeAlerts as $alert) {
            if (!$alert->isViewed()) {
                $hedgeAlertsNotViewed++;
            }
        }

        foreach ($rmpAlerts as $alert) {
            if (!$alert->isViewed()) {
                $rmpAlertsNotViewed++;
            }
        }

        return ['hedgeAlerts' => ['alerts' => $hedgeAlerts, 'notViewedCount' => $hedgeAlertsNotViewed],
            'rmpAlerts' => ['alerts' => $rmpAlerts, 'notViewedCount' => $rmpAlertsNotViewed]];
    }
}
