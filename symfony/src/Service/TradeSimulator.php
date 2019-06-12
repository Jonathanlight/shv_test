<?php

namespace App\Service;

use App\Entity\Hedge;
use App\Constant\Operations;

class TradeSimulator
{
    const TYPE_PARTIAL = 'partial';
    const TYPE_FULL = 'full';

    /**
     * @param Hedge $hedge
     * @param string $type
     * @return array
     */
    public function generateTradesFromHedge(Hedge $hedge, string $type): array
    {
        $trades = [];
        foreach ($hedge->getHedgeLines() as $hedgeLine) {
            if (!$hedgeLine->getQuantity()) {
                continue;
            }

            if ($type == self::TYPE_FULL) {
                $quantity = $hedgeLine->getQuantity() - $hedgeLine->getQuantityRealized();
            } else {
                $quantity = ($hedgeLine->getQuantity() - $hedgeLine->getQuantityRealized()) /2;
            }

            if ($hedge->getOperationType() == Operations::OPERATION_TYPE_BUY) {
                $operationType = 'Buy';
            } else {
                $operationType = 'Sell';
                $quantity = $quantity*-1;
            }

            $currentDate = new \DateTime('now');
            $currentDate = $currentDate->format('YmdHis');
            $trade = [
                abs(crc32(uniqid())),
                abs(crc32(uniqid())),
                $currentDate,
                'APO (L)',
                'Verified',
                $operationType,
                'Put',
                $quantity,
                $currentDate,
                $currentDate,
                $hedge->getRmp()->getBusinessUnit()->getListName(),
                $hedge->getId() . '-' . $hedgeLine->getId(),
                $hedge->getRmp()->getBusinessUnit()->getFullName(),
                'MT',
                $hedge->getHedgingTool()->getName()
            ];
            $trades[] = $trade;
        }

        return $trades;
    }
}