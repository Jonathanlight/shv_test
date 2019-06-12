<?php

namespace App\Command;

use App\Repository\PricerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteOutdatePricerCommand
 * @package App\Command
 */
class DeleteOutdatePricerCommand extends ContainerAwareCommand
{
    /**
     * @var PricerRepository
     */
    protected $pricerRepository;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * DeleteOutdatePricerCommand constructor.
     * @param PricerRepository $pricerRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(PricerRepository $pricerRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->pricerRepository = $pricerRepository;
        $this->entityManager = $entityManager;
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('pricer:files:clean')
            ->setDescription('Delete outdated pricer files')
            ->setHelp('This command allow you to delete pricer files outdated of number of days you can specify. If no number of days is specified, 14 days will be use')
            ->addArgument('numberOfDays', InputArgument::OPTIONAL, "Number of days");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $numberOfDays = $input->getArgument('numberOfDays');

        $numberOfDays = $numberOfDays ?? 14;

        // Delete all outdated entry from database
        $outdatedFiles = $this->pricerRepository->findAllOutdatedInNumberOfDays($numberOfDays);

        if (count($outdatedFiles) > 0) {
            foreach ($outdatedFiles as $outdatedFile) {
                $filePath = $outdatedFile->getFilePath();

                if (file_exists($filePath)) {
                    // Delete physical file
                    if (unlink($filePath)) {

                        // remove in database
                        $this->entityManager->remove($outdatedFile);

                        $output->writeln("Deleted : " . $filePath);
                    } else {
                        $output->writeln("Error when deleting file" . $filePath);
                    }
                }else {
                    $output->writeln("File not exist : " . $filePath);
                }
            }
            $this->entityManager->flush();

            $output->writeln("All outdated pricer files have been deleted");
        } else {
            $output->writeln("No outdated pricer files to delete today");
        }
    }
}
