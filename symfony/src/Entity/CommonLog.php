<?php

namespace App\Entity;

use App\Entity\Traits\LoggableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class CommonLog
{
    use LoggableTrait;

    const BASE_CLASS = 'App\Entity\Common';

    const TYPE_IMPORT_MISSING_VALUES = 1;

    public static $typeLogsLabels = [
        self::TYPE_IMPORT_MISSING_VALUES => 'logs.common.type_import_missing_values',
    ];

    public function getTypeLogsLabels() {
        return self::$typeLogsLabels;
    }
}
