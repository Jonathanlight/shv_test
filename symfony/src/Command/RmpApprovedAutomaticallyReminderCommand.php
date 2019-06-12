<?php

namespace App\Command;

use App\Entity\RMP;
use App\Entity\User;
use App\Service\NotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class RmpApprovedAutomaticallyReminderCommand extends Command
{
    protected $em;

    protected $notificationManager;

    protected $router;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param EntityManagerInterface $em
     * @param NotificationManager $notificationManager
     * @param RouterInterface $router
     */
    public function __construct(EntityManagerInterface $em, NotificationManager $notificationManager, RouterInterface $router)
    {
        parent::__construct();
        $this->em = $em;
        $this->notificationManager = $notificationManager;
        $this->router = $router;
    }

    protected function configure()
    {
        $this
            ->setName('app:rmp:reminder:approved_automatically')
            ->setDescription('Remind BU Hedging committees to validate RMPs')
            ->setHelp('Remind BU Hedging committees to validate RMPs automatically approved for year N');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (date('n') == 1 && date('j') == 1) {

            $output->writeln('Collecting RMPs ...');
            $rmps = $this->em->getRepository(RMP::class)->findBy(['validityPeriod' => date('Y', strtotime('+2 year')),
                                                                            'approvedAutomatically' => true,
                                                                            'status' => RMP::STATUS_APPROVED,
                                                                            'active' => true]);

            foreach ($rmps as $rmp) {
                $buHedgingCommittees = $this->em->getRepository(User::class)->findByRolesAndBusinessUnit([User::ROLE_BU_HEDGING_COMMITTEE],
                                                                                                                    $rmp->getBusinessUnit());

                $this->notificationManager->sendNotification(NotificationManager::TYPE_RMP_APPROVED_AUTOMATICALLY,
                                                            $rmp,
                                                            $buHedgingCommittees,
                                                            ['rmpName' => $rmp->getName(),
                                                            'validityPeriod' => $rmp->getValidityPeriod(),
                                                            'url' => $this->router->generate('rmp_view',
                                                                                            ['rmp' => $rmp->getId()],
                                                                                            UrlGeneratorInterface::ABSOLUTE_URL)]);
            }
        }
    }
}