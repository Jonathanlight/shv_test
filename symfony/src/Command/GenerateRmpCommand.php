<?php

namespace App\Command;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\RMP;
use App\Service\RmpManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateRmpCommand extends Command
{
    protected $em;

    protected $rmpManager;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param bool $requirePassword
     * @param EntityManagerInterface $em
     * @param RmpManager $rmpManager
     */
    public function __construct(bool $requirePassword = false, EntityManagerInterface $em, RmpManager $rmpManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->rmpManager = $rmpManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:rmp:generate')
            ->setDescription('Generate RMPs')
            ->setHelp('This command generates RMP for N+1, N+2 and N+3')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force to collect ALL rmps for current year'
            );
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

        $dateNow = date('Y');
        $dateMax = date('Y', strtotime('+3 years'));

        $rmpAttrs = ['active' => true, 'status' => RMP::STATUS_APPROVED, 'validityPeriod' => $dateNow];
        if (!$input->getOption('force')) {
            $rmpAttrs['n3Exists'] = false;
        }

        $rmpList = $this->em->getRepository(RMP::class)->findBy($rmpAttrs);
        foreach ($rmpList as $rmp) {
            $this->renewRmp($rmp, $output, $dateMax);
            $rmp->setN3Exists(true);
            $this->em->persist($rmp);
            $this->em->flush();
        }

        $output->writeln('End');
    }

    /**
     * @param RMP $rmp
     * @param OutputInterface $output
     * @param int $dateMax
     */
    private function renewRmp(RMP $rmp, OutputInterface $output, int $dateMax)
    {
        $rmpRepository = $this->em->getRepository(RMP::class);
        $rmpNext = $rmpRepository->findNextByRmp($rmp);

        if (!$rmpNext instanceof RMP) {
            $output->writeln('Renewing RMP ('.$rmp->getId().')');

            $newRmp = $this->rmpManager->renewRmp($rmp);
            $newRmp->setName(str_replace([$rmp->getValidityPeriod(), '_V'.$rmp->getVersion()],
                                         [$newRmp->getValidityPeriod(), '_V'.$newRmp->getVersion()],
                                         $newRmp->getName()));
            $this->em->persist($newRmp);

            if ($newRmp->getValidityPeriod() < $dateMax) {
                $this->renewRmp($newRmp, $output, $dateMax);
            }
        } else {
            if ($rmpNext->getValidityPeriod() < $dateMax) {
                $this->renewRmp($rmpNext, $output, $dateMax);
            }
        }
    }
}