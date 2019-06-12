<?php

namespace App\Entity;

use App\Entity\Traits\LoggableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class HedgeLog
{
    use LoggableTrait;

    const TYPE_PENDING_EXECUTION = 1;
    const TYPE_EXTRA_APPROVAL = 2;
    const TYPE_APPROVED_RISK_CONTROLLER = 3;
    const TYPE_REJECTED_RISK_CONTROLLER = 4;
    const TYPE_APPROVED_BOARD_MEMBER = 5;
    const TYPE_REJECTED_BOARD_MEMBER = 6;
    const TYPE_BLOTTER_GENERATION = 7;
    const TYPE_PARTIALLY_REALIZED = 8;
    const TYPE_WRITE_OFF = 9;
    const TYPE_REALIZED = 10;
    const TYPE_CANCELED = 11;
    const TYPE_CANCELLATION_REQUESTED = 12;
    const TYPE_COMMENT = 13;
    const TYPE_CANCELED_AUTOMATICALLY = 14;
    const TYPE_IMPORT = 15;

    public static $typeLogsLabels = [
        self::TYPE_PENDING_EXECUTION => 'logs.hedge.type_pending_execution',
        self::TYPE_EXTRA_APPROVAL => 'logs.hedge.type_extra_approval',
        self::TYPE_APPROVED_RISK_CONTROLLER => 'logs.hedge.type_approved_risk_controller',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'logs.hedge.type_rejected_risk_controller',
        self::TYPE_APPROVED_BOARD_MEMBER => 'logs.hedge.type_approved_board_member',
        self::TYPE_REJECTED_BOARD_MEMBER => 'logs.hedge.type_rejected_board_member',
        self::TYPE_BLOTTER_GENERATION => 'logs.hedge.type_blotter_generation',
        self::TYPE_PARTIALLY_REALIZED => 'logs.hedge.type_partially_realized',
        self::TYPE_WRITE_OFF => 'logs.hedge.type_write_off',
        self::TYPE_REALIZED => 'logs.hedge.type_realized',
        self::TYPE_CANCELED => 'logs.hedge.type_canceled',
        self::TYPE_CANCELLATION_REQUESTED => 'logs.hedge.type_cancellation_requested',
        self::TYPE_COMMENT => 'logs.hedge.type_comment',
        self::TYPE_CANCELED_AUTOMATICALLY => 'logs.hedge.type_canceled_automatically',
        self::TYPE_IMPORT => 'logs.hedge.type_import',
    ];

    public static $typeActionsLabels = [
        self::TYPE_PENDING_EXECUTION => 'actions.hedge.type_pending_execution',
        self::TYPE_EXTRA_APPROVAL => 'actions.hedge.type_extra_approval',
        self::TYPE_APPROVED_RISK_CONTROLLER => 'actions.hedge.type_approved_risk_controller',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'actions.hedge.type_rejected_risk_controller',
        self::TYPE_APPROVED_BOARD_MEMBER => 'actions.hedge.type_approved_board_member',
        self::TYPE_REJECTED_BOARD_MEMBER => 'actions.hedge.type_rejected_board_member',
        self::TYPE_BLOTTER_GENERATION => 'actions.hedge.type_blotter_generation',
        self::TYPE_PARTIALLY_REALIZED => 'actions.hedge.type_partially_realized',
        self::TYPE_WRITE_OFF => 'actions.hedge.type_write_off',
        self::TYPE_REALIZED => 'actions.hedge.type_realized',
        self::TYPE_CANCELED => 'actions.hedge.type_canceled',
        self::TYPE_CANCELLATION_REQUESTED => 'actions.hedge.type_cancellation_requested',
        self::TYPE_CANCELED_AUTOMATICALLY => 'actions.hedge.type_canceled_automatically',
        self::TYPE_IMPORT => 'actions.hedge.type_import',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Hedge")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;

    public function getTypeLogsLabels() {
        return self::$typeLogsLabels;
    }
}
