<?php

namespace App\Command;

use App\Entity\CMS\Letter;
use App\Entity\RMP;
use App\Entity\RmpAlert;
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

class RmpPendingApprovalReminderCommand extends Command
{
    const DEFAULT_INTERVAL = 7;

    protected $em;

    protected $notificationManager;

    protected $router;

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
            ->setName('app:rmp:reminder:pending_approval')
            ->setDescription('Remind risk controllers and board member to validate amendments')
            ->setHelp('Remind risk controllers and board member to validate amendments')
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
        $output->writeln('Collecting RMPs ...');

        $interval = $input->getArgument('interval') ?: self::DEFAULT_INTERVAL;

        $rmps = $this->em->getRepository(RMP::class)->findRemindable($interval);
        $userRepository = $this->em->getRepository(User::class);
        $rmpsRemindable = ['pendingApprovalRiskController' => [], 'pendingApprovalBoardMember' => []];

        foreach ($rmps as $rmp) {
            $businessUnit = $rmp->getBusinessUnit();
            if ($rmp->getStatus() == RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER) {
                $rmpsRemindable['pendingApprovalRiskController'][$businessUnit->getId()][] = [
                    'rmp' => $rmp,
                    'url' => $this->router->generate('rmp_view', ['rmp' => $rmp->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            } else {
                $rmpsRemindable['pendingApprovalBoardMember'][$businessUnit->getId()][] = [
                    'rmp' => $rmp,
                    'url' => $this->router->generate('rmp_view', ['rmp' => $rmp->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ];
            }
        }

        if (count($rmpsRemindable['pendingApprovalRiskController'])) {
            $riskControllers = $userRepository->findByRole(User::ROLE_RISK_CONTROLLER);
            $rmpList = $this->container->get('twig')->render('mail/list_rmp.html.twig', ['rmpsRemindable' => $rmpsRemindable['pendingApprovalRiskController']]);

            foreach ($rmpsRemindable['pendingApprovalRiskController'] as $_rmps) {
                foreach ($_rmps as $_rmp) {
                    $this->alertManager->createAlert($_rmp['rmp'], $riskControllers, RmpAlert::TYPE_REMINDER_RISK_CONTROLLER);
                }
            }

            foreach ($riskControllers as $riskController) {
                $this->mailManager->send(Letter::CODE_RMP_REMINDER_RISK_CONTROLLER, $riskController->getEmail(), ['rmpList' => $rmpList]);
            }
        }

        if (count($rmpsRemindable['pendingApprovalBoardMember'])) {
            $boardMembers = [];
            $rmpList = $this->container->get('twig')->render('mail/list_rmp.html.twig', ['rmpsRemindable' => $rmpsRemindable['pendingApprovalBoardMember']]);

            foreach ($rmpsRemindable['pendingApprovalBoardMember'] as $businessUnitId => $_rmps) {
                foreach ($_rmps as $_rmp) {
                    if (!array_key_exists($businessUnitId, $boardMembers)) {
                        $boardMembers[$businessUnitId] = $userRepository->findByRolesAndBusinessUnit([User::ROLE_BOARD_MEMBER], $_rmp['rmp']->getBusinessUnit());
                    }
                    $this->alertManager->createAlert($_rmp['rmp'], $boardMembers[$businessUnitId], RmpAlert::TYPE_REMINDER_BOARD_MEMBER);
                }
            }

            foreach ($boardMembers as $businessUnitBoardMembers) {
                foreach ($businessUnitBoardMembers as $boardMember) {
                    $this->mailManager->send(Letter::CODE_RMP_REMINDER_BOARD_MEMBER, $boardMember->getEmail(), ['rmpList' => $rmpList]);
                }
            }
        }
    }
}