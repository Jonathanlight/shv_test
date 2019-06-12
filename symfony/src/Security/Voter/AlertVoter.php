<?php

namespace App\Security\Voter;

use App\Entity\HedgeAlert;
use App\Entity\HedgeComment;
use App\Entity\RmpAlert;
use App\Entity\RmpSubSegmentComment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AlertVoter extends Voter
{
    const ALERT_READ = 'alert_read';
    const ALERT_RMP = 'alert_rmp';
    const ALERT_DELETE = 'alert_delete';

    protected $attributes = [
        self::ALERT_READ,
        self::ALERT_RMP,
        self::ALERT_DELETE,
    ];

    /**
     * @param string $attribute
     * @param mixed  $subject
     *
     * @return bool
     */
    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, $this->attributes)) {
            return false;
        }

        return true;
    }

    /**
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::ALERT_READ:
                return $this->canRead($subject, $user);
            case self::ALERT_RMP:
                return $this->canRmp($user);
            case self::ALERT_DELETE:
                return $this->canDelete($subject, $user);
            default:
                return false;
        }
    }

    /**
     * @param HedgeAlert|RmpAlert $alert
     * @param User  $user
     *
     * @return bool
     */
    public function canRead($alert, User $user): bool
    {
        return $alert->getUser() === $user;
    }

    public function canRmp(User $user): bool
    {
        return $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE) || $user->hasRole(User::ROLE_RISK_CONTROLLER)
            || $user->hasRole(User::ROLE_BOARD_MEMBER);
    }

    /**
     * @param HedgeAlert|RmpAlert $alert
     * @param User  $user
     *
     * @return bool
     */
    public function canDelete($alert, User $user): bool
    {
        return $alert->getUser() === $user;
    }
}
