<?php

namespace App\Command;

use App\Entity\RMP;
use App\Entity\RMPLog;
use App\Service\LogManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveRmpCommand extends Command
{
    protected $em;

    protected $rmpManager;

    protected $logManager;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param bool $requirePassword
     * @param EntityManagerInterface $em
     * @param LogManager $logManager
     */
    public function __construct(bool $requirePassword = false, EntityManagerInterface $em, LogManager $logManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->logManager = $logManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:rmp:archive')
            ->setDescription('Archive RMPs outdated')
            ->setHelp('This command archive RMPs for N-1');
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

        $rmps = $this->em->getRepository(RMP::class)->findBy(['validityPeriod' => date('Y', strtotime('-1 year'))]);

        foreach ($rmps as $rmp) {
            if ($rmp->isApproved()) {
                $rmp->setArchivedAutomatically(true);
            }
            $rmp->setStatus(RMP::STATUS_ARCHIVED);
            $this->em->persist($rmp);
            $this->logManager->createLog($rmp, null, RMPLog::TYPE_ARCHIVED);
        }

        $this->em->flush();
    }
}