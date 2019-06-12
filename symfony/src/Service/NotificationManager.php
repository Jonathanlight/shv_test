<?php

namespace App\Service;

use App\Entity\CMS\Letter;
use App\Entity\Hedge;
use App\Entity\HedgeAlert;
use App\Entity\RMP;
use App\Entity\RmpAlert;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class NotificationManager
{
    // Hedge notifications
    const TYPE_HEDGE_PENDING_EXECUTION = 1;
    const TYPE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER = 2;
    const TYPE_HEDGE_PENDING_APPROVAL_BOARD_MEMBER = 3;
    const TYPE_HEDGE_REJECTED_RISK_CONTROLLER = 4;
    const TYPE_HEDGE_REJECTED_BOARD_MEMBER = 5;
    const TYPE_HEDGE_EXTRA_APPROVAL_PENDING_EXECUTION = 6;
    const TYPE_HEDGE_PARTIALLY_REALIZED = 7;
    const TYPE_HEDGE_REALIZED = 8;
    const TYPE_HEDGE_CANCELLATION_REQUESTED = 9;
    const TYPE_HEDGE_CANCELLED = 10;
    const TYPE_HEDGE_CANCELLATION_REFUSED = 11;
    const TYPE_HEDGE_REMINDER_RISK_CONTROLLER = 12;
    const TYPE_HEDGE_REMINDER_BOARD_MEMBER = 13;
    const TYPE_HEDGE_COMMENT = 14;
    const TYPE_HEDGE_CANCELLED_AUTOMATICALLY = 15;

    // RMP notifications
    const TYPE_RMP_PENDING_APPROVAL_RISK_CONTROLLER = 20;
    const TYPE_RMP_PENDING_APPROVAL_BOARD_MEMBER = 21;
    const TYPE_RMP_REJECTED_RISK_CONTROLLER = 22;
    const TYPE_RMP_REJECTED_BOARD_MEMBER = 23;
    const TYPE_RMP_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER = 24;
    const TYPE_RMP_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER = 25;
    const TYPE_RMP_APPROVED = 26;
    const TYPE_RMP_APPROVED_AUTOMATICALLY = 27;
    const TYPE_RMP_REMINDER_RISK_CONTROLLER = 28;
    const TYPE_RMP_REMINDER_BOARD_MEMBER = 29;
    const TYPE_RMP_COMMENT = 30;


    public static $typesAssociations = [
        self::TYPE_HEDGE_PENDING_EXECUTION => [
            'alert' => HedgeAlert::TYPE_PENDING_EXECUTION,
            'letter' => Letter::CODE_HEDGE_PENDING_EXECUTION
        ],
        self::TYPE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER => [
            'alert' => HedgeAlert::TYPE_PENDING_APPROVAL_RISK_CONTROLLER,
            'letter' => Letter::CODE_HEDGE_PENDING_APPROVAL_RISK_CONTROLLER
        ],
        self::TYPE_HEDGE_PENDING_APPROVAL_BOARD_MEMBER => [
            'alert' => HedgeAlert::TYPE_PENDING_APPROVAL_BOARD_MEMBER,
            'letter' => Letter::CODE_HEDGE_PENDING_APPROVAL_BOARD_MEMBER
        ],
        self::TYPE_HEDGE_REJECTED_RISK_CONTROLLER => [
            'alert' => HedgeAlert::TYPE_REJECTED_RISK_CONTROLLER,
            'letter' => Letter::CODE_HEDGE_REJECTED_RISK_CONTROLLER
        ],
        self::TYPE_HEDGE_REJECTED_BOARD_MEMBER => [
            'alert' => HedgeAlert::TYPE_REJECTED_BOARD_MEMBER,
            'letter' => Letter::CODE_HEDGE_REJECTED_BOARD_MEMBER
        ],
        self::TYPE_HEDGE_EXTRA_APPROVAL_PENDING_EXECUTION => [
            'alert' => HedgeAlert::TYPE_EXTRA_APPROVAL_PENDING_EXECUTION,
            'letter' => Letter::CODE_HEDGE_EXTRA_APPROVAL_PENDING_EXECUTION
        ],
        self::TYPE_HEDGE_PARTIALLY_REALIZED => [
            'alert' => HedgeAlert::TYPE_PARTIALLY_REALIZED,
            'letter' => Letter::CODE_HEDGE_PARTIALLY_REALIZED
        ],
        self::TYPE_HEDGE_REALIZED => [
            'alert' => HedgeAlert::TYPE_REALIZED,
            'letter' => Letter::CODE_HEDGE_REALIZED
        ],
        self::TYPE_HEDGE_CANCELLATION_REQUESTED => [
            'alert' => HedgeAlert::TYPE_CANCELLATION_REQUESTED,
            'letter' => Letter::CODE_HEDGE_CANCELLATION_REQUESTED
        ],
        self::TYPE_HEDGE_CANCELLED => [
            'alert' => HedgeAlert::TYPE_CANCELLED,
            'letter' => Letter::CODE_HEDGE_CANCELLED
        ],
        self::TYPE_HEDGE_CANCELLATION_REFUSED => [
            'alert' => HedgeAlert::TYPE_CANCELLATION_REFUSED,
            'letter' => Letter::CODE_HEDGE_CANCELLATION_REFUSED
        ],
        self::TYPE_HEDGE_REMINDER_RISK_CONTROLLER => [
            'alert' => HedgeAlert::TYPE_REMINDER_RISK_CONTROLLER,
            'letter' => Letter::CODE_HEDGE_REMINDER_RISK_CONTROLLER
        ],
        self::TYPE_HEDGE_REMINDER_BOARD_MEMBER => [
            'alert' => HedgeAlert::TYPE_REMINDER_BOARD_MEMBER,
            'letter' => Letter::CODE_HEDGE_REMINDER_BOARD_MEMBER
        ],
        self::TYPE_HEDGE_COMMENT => [
            'alert' => HedgeAlert::TYPE_COMMENT,
            'letter' => Letter::CODE_HEDGE_COMMENT
        ],
        self::TYPE_HEDGE_CANCELLED_AUTOMATICALLY => [
            'alert' => HedgeAlert::TYPE_CANCELLED_AUTOMATICALLY,
            'letter' => Letter::CODE_HEDGE_CANCELLED_AUTOMATICALLY
        ],
        self::TYPE_RMP_PENDING_APPROVAL_RISK_CONTROLLER => [
            'alert' => RmpAlert::TYPE_PENDING_APPROVAL_RISK_CONTROLLER,
            'letter' => Letter::CODE_RMP_PENDING_APPROVAL_RISK_CONTROLLER
        ],
        self::TYPE_RMP_PENDING_APPROVAL_BOARD_MEMBER => [
            'alert' => RmpAlert::TYPE_PENDING_APPROVAL_BOARD_MEMBER,
            'letter' => Letter::CODE_RMP_PENDING_APPROVAL_BOARD_MEMBER
        ],
        self::TYPE_RMP_REJECTED_RISK_CONTROLLER => [
            'alert' => RmpAlert::TYPE_REJECTED_RISK_CONTROLLER,
            'letter' => Letter::CODE_RMP_REJECTED_RISK_CONTROLLER
        ],
        self::TYPE_RMP_REJECTED_BOARD_MEMBER => [
            'alert' => RmpAlert::TYPE_REJECTED_BOARD_MEMBER,
            'letter' => Letter::CODE_RMP_REJECTED_BOARD_MEMBER
        ],
        self::TYPE_RMP_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER => [
            'alert' => RmpAlert::TYPE_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER,
            'letter' => Letter::CODE_RMP_AMENDMENT_PENDING_APPROVAL_RISK_CONTROLLER
        ],
        self::TYPE_RMP_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER => [
            'alert' => RmpAlert::TYPE_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER,
            'letter' => Letter::CODE_RMP_AMENDMENT_PENDING_APPROVAL_BOARD_MEMBER
        ],
        self::TYPE_RMP_APPROVED => [
            'alert' => RmpAlert::TYPE_APPROVED,
            'letter' => Letter::CODE_RMP_APPROVED
        ],
        self::TYPE_RMP_APPROVED_AUTOMATICALLY => [
            'alert' => RmpAlert::TYPE_APPROVED_AUTOMATICALLY,
            'letter' => Letter::CODE_RMP_APPROVED_AUTOMATICALLY
        ],
        self::TYPE_RMP_REMINDER_RISK_CONTROLLER => [
            'alert' => RmpAlert::TYPE_REMINDER_RISK_CONTROLLER,
            'letter' => Letter::CODE_RMP_REMINDER_RISK_CONTROLLER
        ],
        self::TYPE_RMP_REMINDER_BOARD_MEMBER => [
            'alert' => RmpAlert::TYPE_REMINDER_BOARD_MEMBER,
            'letter' => Letter::CODE_RMP_REMINDER_BOARD_MEMBER
        ],
        self::TYPE_RMP_COMMENT => [
            'alert' => RmpAlert::TYPE_COMMENT,
            'letter' => Letter::CODE_RMP_COMMENT
        ],
    ];

    private $em;

    private $mailManager;

    private $alertManager;

    public function __construct(EntityManagerInterface $em, MailManager $mailManager, AlertManager $alertManager)
    {
        $this->em = $em;
        $this->mailManager = $mailManager;
        $this->alertManager = $alertManager;
    }

    /**
     * @param int $type
     * @param Hedge|RMP $entity
     * @param array $recipients
     * @param array $bindings
     * @param string $additionalMessage
     * @param array $attachments
     *
     */
    public function sendNotification(int $type, $entity, array $recipients, array $bindings, ?string $additionalMessage = null, ?array $attachments = null)
    {
        foreach ($recipients as $recipient) {
            $this->mailManager->send(self::$typesAssociations[$type]['letter'], $recipient->getEmail(), $bindings, $attachments);
        }
        $this->alertManager->createAlert($entity, $recipients, self::$typesAssociations[$type]['alert'], $additionalMessage);
    }
}