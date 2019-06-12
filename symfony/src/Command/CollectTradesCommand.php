<?php

namespace App\Command;

use App\Entity\Trade;
use App\Service\Api\CXLClientService;
use App\Service\ImportManager;
use App\Service\TradeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class CollectTradesCommand extends Command
{
    protected $em;

    protected $importManager;

    protected $tradeManager;

    protected $cXLClientService;

    protected $projectPath;

    protected $params;

    protected $logger;

    /**
     * CollectTradesCommand constructor.
     *
     * @param EntityManagerInterface $em
     * @param ImportManager $importManager
     * @param TradeManager $tradeManager
     * @param string $projectPath
     * @param CXLClientService $cXLClientService
     * @param ParameterBagInterface $params
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, ImportManager $importManager, TradeManager $tradeManager, string $projectPath, CXLClientService $cXLClientService, ParameterBagInterface $params, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->importManager = $importManager;
        $this->tradeManager = $tradeManager;
        $this->projectPath = $projectPath;
        $this->cXLClientService = $cXLClientService;
        $this->params = $params;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('app:trade:collect')
            ->setDescription('Collect trades from CXL')
            ->setHelp('This command collect trades by datetime')
            ->addArgument('dateFrom', InputArgument::OPTIONAL, 'Collect trades from which date ?')
            ->addArgument('dateTo', InputArgument::OPTIONAL, 'Collect trades to which date ?')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Find values in default file instead of API (<dateFrom> and <dateTo> will be ignored)'
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
        $output->writeln('Collecting Trades ...');
        $file = $input->getOption('file');
        $tradesAsString = null;

        if (($filename = $input->getOption('file'))) {
            $file = $this->projectPath . '/' . $this->params->get('cxl.import_file_dir') . '/' . $filename;

            if (file_exists($file)) {
                $tradesAsString = file_get_contents($file);
            }

            if (!$tradesAsString) {
                $messageError = 'An error occurred when collecting trades with filename : ' . $filename;
                $output->writeln($messageError);
                $this->logger->error($messageError);
            }
        } else {

            $dateTo = $input->getArgument('dateTo');
            if (!$dateTo) {
                $dateTo = date('YmdHis');
            }

            $dateFrom = $input->getArgument('dateFrom');
            if (!$dateFrom) {
                $dateFrom = date('YmdHis', strtotime('-5 minutes'));
            }

            $tradesAsString = $this->cXLClientService->getTrades($dateFrom, $dateTo, $dateFrom, $dateTo);
        }

        if ($tradesAsString) {
            // ====> Extract string in return
            $xml_decoded = html_entity_decode($tradesAsString);
            $xml = new \SimpleXMLElement($xml_decoded);
            $body = $xml->xpath('//return')[0];
            $entities = json_decode(json_encode((array)$body), TRUE);
            $tradesAsString = $entities[0];
            // <==== Extract string

            $tradesAsArray = $this->importManager->parseTPTValues($tradesAsString, Trade::IMPORT_NB_COLS);
            $this->tradeManager->processTrades($tradesAsArray);
        }

        $output->writeln('End');
    }
}
