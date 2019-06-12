<?php

namespace App\Command\MasterData;

use App\Entity\MasterData\Maturity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMaturitiesCommand extends Command
{
    protected $em;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param bool $requirePassword
     * @param EntityManagerInterface $em
     */
    public function __construct(bool $requirePassword = false, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('app:maturity:generate')
            ->setDescription('Generate maturities')
            ->setHelp('This command generate maturities');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generating maturities ...');

        for ($i = 0; $i < 4; $i++) {
            $year = date('Y', strtotime('+'.$i.' years'));
            $maturities = $this->em->getRepository(Maturity::class)->findBy(['year' => $year]);

            if (count($maturities) < 12) {
                $this->generateMaturitiesByYear($maturities, $year);
            }
        }
    }

    /**
     * @param array $maturities
     * @param int $year
     */
    private function generateMaturitiesByYear(array $maturities, int $year)
    {
        $missingMonths = [1,2,3,4,5,6,7,8,9,10,11,12];

        foreach ($maturities as $maturity) {
            unset($missingMonths[$maturity->getMonth()-1]);
        }

        foreach ($missingMonths as $month) {
            $maturityDate = $year.'-'.$month.'-01';

            $_maturity = new Maturity();
            $_maturity->setActive(1);
            $_maturity->setMonth($month);
            $_maturity->setYear($year);
            $_maturity->setDay(1);
            $_maturity->setName(date('M', strtotime($maturityDate)).'-'.date('y', strtotime($maturityDate)));

            $this->em->persist($_maturity);
            $this->em->flush();
        }
    }
}