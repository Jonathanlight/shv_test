<?php

namespace App\Constant;

class Instruments
{
    const INSTRUMENT_CALL = 1;
    const INSTRUMENT_PUT = -1;
    const INSTRUMENT_SWAP = 2;

    public static $instrumentType = [
        self::INSTRUMENT_PUT => 'Put',
        self::INSTRUMENT_CALL => 'Call',
        self::INSTRUMENT_SWAP => '',
    ];
}
