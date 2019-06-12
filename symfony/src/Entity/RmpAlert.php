<?php

namespace App\Entity;

use App\Entity\Traits\AlertableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class RmpAlert
{
    use AlertableTrait;

    const TYPE_PENDING_APPROVAL_RISK_CONTROLLER = 20;
    const TYPE_PENDING_APPROVAL_BOARD_MEMBER = 21;
    const TYPE_REJECTED_RISK_CONTROLLER = 22;
    const TYPE_REJECTED_BOARD_MEMBER = 23;
    const TYPE_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER = 24;
    const TYPE_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER = 25;
    const TYPE_APPROVED = 26;
    const TYPE_APPROVED_AUTOMATICALLY = 27;
    const TYPE_REMINDER_RISK_CONTROLLER = 28;
    const TYPE_REMINDER_BOARD_MEMBER = 29;
    const TYPE_COMMENT = 30;

    public static $typeLabels = [
        self::TYPE_PENDING_APPROVAL_RISK_CONTROLLER => 'alerts.rmp.type_pending_approval_risk_controller',
        self::TYPE_PENDING_APPROVAL_BOARD_MEMBER => 'alerts.rmp.type_pending_approval_board_member',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'alerts.rmp.type_rejected_risk_controller',
        self::TYPE_REJECTED_BOARD_MEMBER => 'alerts.rmp.type_rejected_board_member',
        self::TYPE_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER => 'alerts.rmp.type_amendment_pending_approval_risk_controller',
        self::TYPE_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER => 'alerts.rmp.type_amendment_pending_approval_board_member',
        self::TYPE_APPROVED => 'alerts.rmp.type_approved',
        self::TYPE_APPROVED_AUTOMATICALLY => 'alerts.rmp.type_approved_automatically',
        self::TYPE_REMINDER_RISK_CONTROLLER => 'alerts.rmp.type_reminder_risk_controller',
        self::TYPE_REMINDER_BOARD_MEMBER => 'alerts.rmp.type_reminder_board_member',
        self::TYPE_COMMENT => 'alerts.rmp.type_comment',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;
}
