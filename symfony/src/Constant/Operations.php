<?php

namespace App\Constant;

class Operations
{
    const OPERATION_TYPE_BUY = 1;
    const OPERATION_TYPE_SELL = 2;

    public static $operationTypeChoices = [
        self::OPERATION_TYPE_BUY => 'hedge.view.buy',
        self::OPERATION_TYPE_SELL => 'hedge.view.sell',
    ];

    // This values are inverted for import. When Trader 'Buy', BU 'Sell' and when Trader 'Sell', BU 'Buy'
    public static $operationTypeForImportInverted = [
        self::OPERATION_TYPE_BUY => 'Sell',
        self::OPERATION_TYPE_SELL => 'Buy',
    ];
}
