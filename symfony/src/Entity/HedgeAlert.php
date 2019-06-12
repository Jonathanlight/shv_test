<?php

namespace App\Entity;

use App\Entity\Traits\AlertableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class HedgeAlert
{
    use AlertableTrait;

    const TYPE_PENDING_EXECUTION = 1;
    const TYPE_PENDING_APPROVAL_RISK_CONTROLLER = 2;
    const TYPE_PENDING_APPROVAL_BOARD_MEMBER = 3;
    const TYPE_REJECTED_RISK_CONTROLLER = 4;
    const TYPE_REJECTED_BOARD_MEMBER = 5;
    const TYPE_EXTRA_APPROVAL_PENDING_EXECUTION = 6;
    const TYPE_PARTIALLY_REALIZED = 7;
    const TYPE_REALIZED = 8;
    const TYPE_CANCELLATION_REQUESTED = 9;
    const TYPE_CANCELLED = 10;
    const TYPE_CANCELLATION_REFUSED = 11;
    const TYPE_REMINDER_RISK_CONTROLLER = 12;
    const TYPE_REMINDER_BOARD_MEMBER = 13;
    const TYPE_COMMENT = 14;
    const TYPE_REMINDER_TRADER = 15;
    const TYPE_CANCELLED_AUTOMATICALLY = 16;

    public static $typeLabels = [
        self::TYPE_PENDING_EXECUTION => 'alerts.hedge.type_pending_execution',
        self::TYPE_PENDING_APPROVAL_RISK_CONTROLLER => 'alerts.hedge.type_pending_approval_risk_controller',
        self::TYPE_PENDING_APPROVAL_BOARD_MEMBER => 'alerts.hedge.type_pending_approval_board_member',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'alerts.hedge.type_rejected_risk_controller',
        self::TYPE_REJECTED_BOARD_MEMBER => 'alerts.hedge.type_rejected_board_member',
        self::TYPE_EXTRA_APPROVAL_PENDING_EXECUTION => 'alerts.hedge.type_extra_approval_pending_execution',
        self::TYPE_PARTIALLY_REALIZED => 'alerts.hedge.type_partially_realized',
        self::TYPE_REALIZED => 'alerts.hedge.type_realized',
        self::TYPE_CANCELLATION_REQUESTED => 'alerts.hedge.type_cancellation_requested',
        self::TYPE_CANCELLED => 'alerts.hedge.type_cancelled',
        self::TYPE_CANCELLATION_REFUSED => 'alerts.hedge.type_cancellation_refused',
        self::TYPE_REMINDER_RISK_CONTROLLER => 'alerts.hedge.type_reminder_risk_controller',
        self::TYPE_REMINDER_BOARD_MEMBER => 'alerts.hedge.type_reminder_board_member',
        self::TYPE_COMMENT => 'alerts.hedge.type_comment',
        self::TYPE_REMINDER_TRADER => 'alerts.hedge.type_reminder_trader',
        self::TYPE_CANCELLED_AUTOMATICALLY => 'alerts.hedge.type_canceled_automatically',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Hedge")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;
}
