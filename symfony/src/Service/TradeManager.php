<?php

namespace App\Service;

use App\Entity\Hedge;
use App\Entity\Trade;
use App\Entity\HedgeLog;
use App\Entity\HedgeLine;
use App\Constant\Operations;
use Psr\Log\LoggerInterface;
use App\Constant\Instruments;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TradeManager
{
    const TRADE_NUMBER = 1;
    const TRADE_DATE = 2;
    const TRADE_TYPE = 3;
    const TRADE_STATUS = 4;
    const TRADE_OPERATION = 5;
    const TRADE_INSTRUMENT = 6;
    const TRADE_QUANTITY = 7;
    const TRADE_CYLIPOL_ID = 11;
    const TRADE_PRICE = 15;
    const TRADE_STRIKE_PRICE = 16;
    const TRADE_MODIFIED_AT = 17;

    const TRADE_TYPE_APO = 'APO (L)';
    const TRADE_TYPE_SWAP = 'Swap (L)';

    private $em;

    private $logManager;

    private $tokenStorage;

    private $logger;

    private $rmpSubSegmentManager;

    /**
     * TradeManager constructor.
     * @param EntityManagerInterface $em
     * @param LogManager $logManager
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     * @param RmpSubSegmentManager $rmpSubSegmentManager
     */
    public function __construct(EntityManagerInterface $em, LogManager $logManager, TokenStorageInterface $tokenStorage,
                                LoggerInterface $logger, RmpSubSegmentManager $rmpSubSegmentManager)
    {
        $this->em = $em;

        $this->logManager = $logManager;

        $this->tokenStorage = $tokenStorage;

        $this->logger = $logger;

        $this->rmpSubSegmentManager = $rmpSubSegmentManager;
    }

    /**
     * @param array $tradesAsArray
     */
    public function processTrades(array $tradesAsArray)
    {
        $hedgeLines = $this->importTrades($tradesAsArray);

        $checkedHedges = [];
        foreach ($hedgeLines as $hedgeLine) {
            $hedge = $hedgeLine->getHedge();
            $this->updateHedgeLineQuantity($hedgeLine);
            if (!isset($checkedHedges[$hedge->getId()])) {
                $checkedHedges[$hedge->getId()] = $hedge;
            }
        }

        foreach($checkedHedges as $hedge) {
            $this->updateHedgeStatus($hedge);
        }

        foreach ($hedgeLines as $hedgeLine) {
            $this->rmpSubSegmentManager->calculateVolumes($hedgeLine->getRmpSubSegment());
        }
    }

    /**
     * Update Hedge status
     *
     * @param Hedge $hedge
     * @return void
     */
    private function updateHedgeStatus(Hedge $hedge)
    {
        if (!$this->isPartiallyRealizedHedge($hedge)) {
            if ($hedge->getStatus() == Hedge::STATUS_PENDING_EXECUTION) {
                $hedge->setPartiallyRealized(false);
                $hedge->setStatus(Hedge::STATUS_REALIZED);
                $this->logManager->createLog($hedge,  null, HedgeLog::TYPE_REALIZED);
            }
        } else {
            $hedge->setStatus(Hedge::STATUS_PENDING_EXECUTION);
            $hedge->setPartiallyRealized(true);
            $this->logManager->createLog($hedge,  null, HedgeLog::TYPE_PARTIALLY_REALIZED);
        }

        $this->em->persist($hedge);
        $this->em->flush();
    }

    /**
     * Update hedgeLine quantity
     *
     * @param HedgeLine $hedgeLine
     * @return void
     */
    private function updateHedgeLineQuantity(HedgeLine $hedgeLine)
    {
        $hedgeLineTotalQuantity = 0;
        $tradesByQuantity = [];
        $operationsCount = count($hedgeLine->getHedge()->getHedgingTool()->getOperationsAsArray());
        $tradesCount = $hedgeLine->getTrades()->count();

        if ($tradesCount % $operationsCount != 0) {
            $this->logger->error("Trades operation number not correct for HedgeLine : " . $hedgeLine->getId());
        }

        foreach ($hedgeLine->getTrades() as $trade) {
            $tradesByQuantity[$trade->getQuantity()][] = $trade->getId();
        }

        foreach ($tradesByQuantity as $quantity => $tradeIds) {
            $tradesCount = count($tradeIds);
            $exceededTrades = ($tradesCount % $operationsCount);
            if ($exceededTrades == 0) {
                $hedgeLineTotalQuantity += ($tradesCount / $operationsCount) * $quantity;
            } else {
                $hedgeLineTotalQuantity += (($tradesCount - $exceededTrades) / $operationsCount) * $quantity;
                $this->logger->error("Import error with trades IDs : " . implode(',', $tradeIds));
            }
        }

        $hedgeLine->setQuantityRealized($hedgeLineTotalQuantity);

        $this->em->persist($hedgeLine);
        $this->em->flush();
    }

    /**
     * Check whether Hedge is partially realized or not
     *
     * @param Hedge $hedge
     * @return boolean
     */
    private function isPartiallyRealizedHedge(Hedge $hedge)
    {
        $hedgeLines = $hedge->getHedgeLines();

        foreach ($hedgeLines as $hedgeLine) {
            if ($hedgeLine->isPartiallyRealized()) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param array $tradesAsArray
     * @return ArrayCollection
     */
    private function importTrades(array $tradesAsArray): ArrayCollection
    {
        $hedgeLineRepository = $this->em->getRepository(HedgeLine::class);

        $hedgeLines = new ArrayCollection();

        foreach ($tradesAsArray as $trade) {
            if (
                !empty($trade[self::TRADE_CYLIPOL_ID]) &&
                count($trade) == Trade::IMPORT_NB_COLS &&
                preg_match('/[0-9]+-[0-9]+/', $trade[self::TRADE_CYLIPOL_ID])
            ) {
                $tradeEntity = $this->em->getRepository(Trade::class)->findOneBy([
                    'cxlTradeNumber' => $trade[self::TRADE_NUMBER],
                ]);

                if ($trade[self::TRADE_STATUS] == Trade::STATUS_VOID) {
                    if ($tradeEntity) {
                        $hedgeLines->add($tradeEntity->getHedgeLine());
                        $this->em->remove($tradeEntity);
                    }
                    continue;
                }

                if (!$tradeEntity) {
                    $tradeEntity = new Trade();
                }

                $hedgeLineId = explode('-', $trade[self::TRADE_CYLIPOL_ID])[1];
                $hedgeLine = $hedgeLineRepository->find($hedgeLineId);

                if ($hedgeLine instanceof HedgeLine) {
                    $tradeEntity->setQuantity(abs($trade[self::TRADE_QUANTITY]));
                    $tradeEntity->setCxlTradeNumber($trade[self::TRADE_NUMBER]);
                    $tradeEntity->setTradingDate(new \DateTime($trade[self::TRADE_MODIFIED_AT]));
                    $tradeEntity->setHedgeLine($hedgeLine);
                    $tradeEntity->setOperationType(array_search($trade[self::TRADE_OPERATION], Operations::$operationTypeChoices));
                    $tradeEntity->setInstrument(array_search($trade[self::TRADE_INSTRUMENT], Instruments::$instrumentType));
                    $tradeEntity->setStatus($trade[self::TRADE_STATUS]);

                    if ($trade[self::TRADE_TYPE] == self::TRADE_TYPE_APO) {
                        $setterStrike = 'set' . $trade[self::TRADE_INSTRUMENT] . 'Strike';
                        $setterPremium = 'set' . $trade[self::TRADE_INSTRUMENT] . 'Premium';

                        $tradeEntity->$setterStrike((float)$trade[self::TRADE_STRIKE_PRICE]);
                        $tradeEntity->$setterPremium((float)$trade[self::TRADE_PRICE]);
                    } else {
                        $tradeEntity->setSwapPrice($trade[self::TRADE_PRICE]);
                    }

                    $this->em->persist($tradeEntity);
                    $this->em->flush();

                    $hedgeLines->add($hedgeLine);
                } else {
                    $this->logger->error("No hedge line found with trade ID : $hedgeLineId");
                }
            } else {
                $this->logger->error("Trade doesn't exists from API");
            }
        }

        return $hedgeLines;
    }
}
