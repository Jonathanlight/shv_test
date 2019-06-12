<?php

namespace App\Entity;

use App\Entity\Traits\LoggableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class RMPLog
{
    use LoggableTrait;

    const TYPE_AMENDMENT_REQUEST = 1;
    const TYPE_APPROVED_RISK_CONTROLLER = 2;
    const TYPE_REJECTED_RISK_CONTROLLER = 3;
    const TYPE_APPROVED_BOARD_MEMBER = 4;
    const TYPE_REJECTED_BOARD_MEMBER = 5;
    const TYPE_ARCHIVED = 6;
    const TYPE_BLOCKED = 7;
    const TYPE_COMMENT = 13;

    public static $typeLogsLabels = [
        self::TYPE_AMENDMENT_REQUEST => 'logs.rmp.type_amendment_request',
        self::TYPE_APPROVED_RISK_CONTROLLER => 'logs.rmp.type_approved_risk_controller',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'logs.rmp.type_rejected_risk_controller',
        self::TYPE_APPROVED_BOARD_MEMBER => 'logs.rmp.type_approved_board_member',
        self::TYPE_REJECTED_BOARD_MEMBER => 'logs.rmp.type_rejected_board_member',
        self::TYPE_ARCHIVED => 'logs.rmp.type_archived',
        self::TYPE_BLOCKED => 'logs.rmp.type_blocked',
        self::TYPE_COMMENT => 'logs.rmp.type_comment',
    ];

    public static $typeActionsLabels = [
        self::TYPE_AMENDMENT_REQUEST => 'actions.rmp.type_amendment_request',
        self::TYPE_APPROVED_RISK_CONTROLLER => 'actions.rmp.type_approved_risk_controller',
        self::TYPE_REJECTED_RISK_CONTROLLER => 'actions.rmp.type_rejected_risk_controller',
        self::TYPE_APPROVED_BOARD_MEMBER => 'actions.rmp.type_approved_board_member',
        self::TYPE_REJECTED_BOARD_MEMBER => 'actions.rmp.type_rejected_board_member',
        self::TYPE_ARCHIVED => 'actions.rmp.type_archived',
        self::TYPE_BLOCKED => 'actions.rmp.type_blocked',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RMP")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent;

    public function getTypeLogsLabels() {
        return self::$typeLogsLabels;
    }
}
