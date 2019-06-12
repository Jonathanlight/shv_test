<?php

namespace App\Service\Excel\SheetContents;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SwapSheet extends BaseSheet
{
    const TITLE = 'Swap';
    const HEADER = [
        'Trade Date', 'Counterpart', 'Buy/Sell', 'Float', 'Qty', 'Qty UOM', 'Fixed Price', 'Price Ccy',
        'Price UOM', 'Start Date', 'End Date', 'Venture', 'Internal Company', 'Strategy', 'Ref', 'Settlement Ccy',
        'Payment', 'Trader', 'Ref2', 'What If', 'MARKET SEGMENTATION', 'WAIVER', 'WAIVER CLASS RISK', 'DIFF_PRICE_LEG',
        'Qty Periodicity', 'Schedule', 'Fx Quote', 'Fx Event', 'SUB-SEGMENT', 'Hedging Tool Class Risk', 'MARKET PRICE RISK'
    ];

    /**
     * SwapSheet constructor.
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
            'swap',
            'swap1',
            'swap2',
            'swaps'
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