<?php

namespace App\Service\Excel\SheetContents;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\PriceRiskClassification;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ApoSheet extends BaseSheet
{
    const TITLE = 'APO';
    const HEADER = [
        'Trade Date', 'Counterpart', 'Buy/Sell', 'Underlying Quote', 'Put/Call', 'Qty', 'Qty UOM', 'Strike', 'Strike Price Ccy',
        'Strike Price UOM', 'Premium', 'Premium Ccy', 'Premium UOM', 'Start Date', 'End Date', 'Venture', 'Internal Company',
        'Strategy', 'Ref', 'Settlement Ccy', 'Prem Payment', 'Payment', 'Trader', 'Option Style', 'Exercise Freq', 'Ref2', 'What If',
        'Qty Periodicity', 'Schedule', 'MARKET SEGMENTATION', 'WAIVER', 'WAIVER CLASS RISK', 'DIFF_PRICE_LEG', 'SUB-SEGMENT',
        'Hedging Tool Class Risk', 'MARKET PRICE RISK'
    ];

    /**
     * ApoSheet constructor.
     * @param EntityManagerInterface $em
     * @param Hedge $hedge
     * @param User $user
     */
    public function __construct(EntityManagerInterface $em, Hedge $hedge, User $user)
    {
        parent::__construct($em, $hedge, $user, self::TITLE, self::HEADER);
    }


    /**
     * @param HedgeLine $hedgeLine
     * @return array
     */
    protected function getLines(HedgeLine $hedgeLine): array
    {
        $this->allowedOperations = [
            'call',
            'call1',
            'call2',
            'put',
            'put1',
            'put2',
        ];

        return parent::getLines($hedgeLine);
    }

    /**
     * @return array
     */
    protected function getLine(): array
    {
        $this->header = self::HEADER;

        return parent::getLine();
    }

}