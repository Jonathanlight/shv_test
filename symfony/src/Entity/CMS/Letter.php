<?php

namespace App\Entity\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Letter
{
    // Hedge mails
    const CODE_HEDGE_PENDING_EXECUTION = 'hedge.pending_execution';
    const CODE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER = 'hedge.pending_approval_risk_controller';
    const CODE_HEDGE_PENDING_APPROVAL_BOARD_MEMBER = 'hedge.pending_approval_board_member';
    const CODE_HEDGE_REJECTED_RISK_CONTROLLER = 'hedge.rejected_risk_controller';
    const CODE_HEDGE_REJECTED_BOARD_MEMBER = 'hedge.rejected_board_member';
    const CODE_HEDGE_EXTRA_APPROVAL_PENDING_EXECUTION = 'hedge.extra_approval_pending_execution';
    const CODE_HEDGE_PARTIALLY_REALIZED = 'hedge.partially_realized';
    const CODE_HEDGE_REALIZED = 'hedge.realized';
    const CODE_HEDGE_CANCELLATION_REQUESTED = 'hedge.cancellation_requested';
    const CODE_HEDGE_CANCELLED = 'hedge.cancelled';
    const CODE_HEDGE_CANCELLATION_REFUSED = 'hedge.cancellation_refused';
    const CODE_HEDGE_REMINDER_RISK_CONTROLLER = 'hedge.reminder_risk_controller';
    const CODE_HEDGE_REMINDER_BOARD_MEMBER = 'hedge.reminder_board_member';
    const CODE_HEDGE_REMINDER_TRADER = 'hedge.reminder_trader';
    const CODE_HEDGE_COMMENT = 'hedge.comment';
    const CODE_HEDGE_CANCELLED_AUTOMATICALLY = 'hedge.cancelled_automatically';

    // RMP mails
    const CODE_RMP_PENDING_APPROVAL_RISK_CONTROLLER = 'rmp.pending_approval_risk_controller';
    const CODE_RMP_PENDING_APPROVAL_BOARD_MEMBER = 'rmp.pending_approval_board_member';
    const CODE_RMP_REJECTED_RISK_CONTROLLER = 'rmp.rejected_risk_controller';
    const CODE_RMP_REJECTED_BOARD_MEMBER = 'rmp.rejected_board_member';
    const CODE_RMP_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER = 'rmp.amendment_pending_approval_risk_controller';
    const CODE_RMP_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER = 'rmp.amendment_pending_approval_board_member';
    const CODE_RMP_APPROVED = 'rmp.approved';
    const CODE_RMP_APPROVED_AUTOMATICALLY = 'rmp.approved_automatically';
    const CODE_RMP_REMINDER_RISK_CONTROLLER = 'rmp.reminder_risk_controller';
    const CODE_RMP_REMINDER_BOARD_MEMBER = 'rmp.reminder_board_member';
    const CODE_RMP_COMMENT = 'rmp.comment';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $content;

    /**
     * @var string
     * @ORM\Column()
     */
    protected $subject;

    /**
     * @var string
     * @ORM\Column()
     */
    protected $code;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}