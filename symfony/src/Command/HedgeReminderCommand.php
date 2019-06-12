<?php

namespace App\Command;

use App\Entity\CMS\Letter;
use App\Entity\Hedge;
use App\Entity\HedgeAlert;
use App\Entity\User;
use App\Service\AlertManager;
use App\Service\MailManager;
use App\Service\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class HedgeReminderCommand extends Command
{

    const DEFAULT_INTERVAL = 3;

    private $em;

    private $notificationManager;

    private $router;

    private $container;

    private $alertManager;

    private $mailManager;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param EntityManagerInterface $em
     * @param NotificationManager $notificationManager
     * @param RouterInterface $router
     * @param ContainerInterface $container
     * @param AlertManager $alertManager
     * @param MailManager $mailManager
     */
    public function __construct(EntityManagerInterface $em, NotificationManager $notificationManager, RouterInterface $router,
                                ContainerInterface $container, AlertManager $alertManager, MailManager $mailManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->notificationManager = $notificationManager;
        $this->router = $router;
        $this->container = $container;
        $this->alertManager = $alertManager;
        $this->mailManager = $mailManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:hedge:reminder')
            ->setDescription('Remind pending approvals hedges')
            ->setHelp('This command send notifications to RC and BM to remind them when a hedge stay for X days with status pending approval')
            ->addArgument('interval', InputArgument::OPTIONAL, 'Number of days after which reminders are sent');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Collecting Hedges ...');

        $interval = $input->getArgument('interval') ?: self::DEFAULT_INTERVAL;

        $hedges = $this->em->getRepository(Hedge::class)->findRemindable($interval);
        $userRepository = $this->em->getRepository(User::class);

        $hedgesRemindable = ['pendingApprovalRiskController' => [], 'pendingApprovalBoardMember' => [], 'pendingExecution' => []];
        foreach ($hedges as $hedge) {
            $businessUnit = $hedge->getRmp()->getBusinessUnit();
            if ($hedge->isPendingExecution()) {
                $hedgesRemindable['pendingExecution'][$businessUnit->getId()][] = [
                    'hedge' => $hedge,
                    'url' => $this->router->generate('hedge_edit', ['hedge' => $hedge->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            } else if ($hedge->getStatus() == Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
                $hedgesRemindable['pendingApprovalRiskController'][$businessUnit->getId()][] = [
                    'hedge' => $hedge,
                    'url' => $this->router->generate('hedge_edit', ['hedge' => $hedge->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            } else {
                $hedgesRemindable['pendingApprovalBoardMember'][$businessUnit->getId()][] = [
                    'hedge' => $hedge,
                    'url' => $this->router->generate('hedge_edit', ['hedge' => $hedge->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            }
        }

        if (count($hedgesRemindable['pendingExecution'])) {
            $traders = $userRepository->findByRole(User::ROLE_TRADER);
            $hedgeList = $this->container->get('twig')->render('mail/list_hedge.html.twig', ['hedgesRemindable' => $hedgesRemindable['pendingExecution']]);

            foreach ($hedgesRemindable['pendingExecution'] as $_hedges) {
                foreach ($_hedges as $_hedge) {
                    $this->alertManager->createAlert($_hedge['hedge'], $traders, HedgeAlert::TYPE_REMINDER_TRADER);
                }
            }

            foreach ($traders as $trader) {
                $this->mailManager->send(Letter::CODE_HEDGE_REMINDER_TRADER, $trader->getEmail(), ['hedgeList' => $hedgeList]);
            }
        }

        if (count($hedgesRemindable['pendingApprovalRiskController'])) {
            $riskControllers = $userRepository->findByRole(User::ROLE_RISK_CONTROLLER);
            $hedgeList = $this->container->get('twig')->render('mail/list_hedge.html.twig', ['hedgesRemindable' => $hedgesRemindable['pendingApprovalRiskController']]);

            foreach ($hedgesRemindable['pendingApprovalRiskController'] as $_hedges) {
                foreach ($_hedges as $_hedge) {
                    $this->alertManager->createAlert($_hedge['hedge'], $riskControllers, HedgeAlert::TYPE_REMINDER_RISK_CONTROLLER);
                }
            }

            foreach ($riskControllers as $riskController) {
                $this->mailManager->send(Letter::CODE_HEDGE_REMINDER_RISK_CONTROLLER, $riskController->getEmail(), ['hedgeList' => $hedgeList]);
            }
        }

        if (count($hedgesRemindable['pendingApprovalBoardMember'])) {
            $boardMembers = [];
            $hedgeList = $this->container->get('twig')->render('mail/list_hedge.html.twig', ['hedgesRemindable' => $hedgesRemindable['pendingApprovalBoardMember']]);

            foreach ($hedgesRemindable['pendingApprovalBoardMember'] as $businessUnitId => $_hedges) {
                foreach ($_hedges as $_hedge) {
                    if (!array_key_exists($businessUnitId, $boardMembers)) {
                        $boardMembers[$businessUnitId] = $userRepository->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $_hedge['hedge']->getRmp()->getBusinessUnit());
                    }
                    $this->alertManager->createAlert($_hedge['hedge'], $boardMembers[$businessUnitId], HedgeAlert::TYPE_REMINDER_BOARD_MEMBER);
                }
            }

            foreach ($boardMembers as $businessUnitBoardMembers) {
                foreach ($businessUnitBoardMembers as $boardMember) {
                    $this->mailManager->send(Letter::CODE_HEDGE_REMINDER_BOARD_MEMBER, $boardMember->getEmail(), ['hedgeList' => $hedgeList]);
                }
            }
        }
    }
}