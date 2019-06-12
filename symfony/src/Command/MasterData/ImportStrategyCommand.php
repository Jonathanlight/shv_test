<?php

namespace App\Command\MasterData;

use App\Entity\MasterData\Strategy;
use App\Service\Api\CXLClientService;
use App\Service\ImportManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class ImportStrategyCommand extends Command
{
    protected $em;

    protected $importManager;

    protected $projectPath;

    protected $cXLClientService;

    private $params;

    protected $logger;

    /**
     * GenerateRmpCommand constructor.
     *
     * @param EntityManagerInterface $em
     * @param ImportManager $importManager
     * @param string $projectPath
     * @param CXLClientService $cXLClientService
     */
    public function __construct(EntityManagerInterface $em, ImportManager $importManager, string $projectPath, CXLClientService $cXLClientService, ParameterBagInterface $params, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->importManager = $importManager;
        $this->projectPath = $projectPath;
        $this->cXLClientService = $cXLClientService;
        $this->params = $params;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('app:strategy:import')
            ->setDescription('Import strategies')
            ->setHelp('This command import strategies from CXL')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Find values in default file instead of API'
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
        $output->writeln('Collecting Strategies ...');

        $file = $input->getOption('file');
        $values = null;

        if (($filename = $input->getOption('file'))) {
            $file = $this->projectPath . '/' . $this->params->get('cxl.import_file_dir') . '/' . $filename;

            if (file_exists($file)) {
                $values = file_get_contents($file);
            }

            if (!$values) {
                $messageError = 'An error occurred when importing strategies with filename :' . $filename;
                $output->writeln($messageError);
                $this->logger->error($messageError);
            }
        } else {
            $values = $this->cXLClientService->getStrategies();
        }

        $output->writeln('Importing Strategies ...');

        if ($values) {
            $this->importManager->import(
                Strategy::class,
                Strategy::$importXMLColsMapping,
                Strategy::IMPORT_IDENTIFIER_INDEX,
                $values
            );
        }

        $output->writeln('End');
    }
}
