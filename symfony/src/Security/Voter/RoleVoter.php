<?php

namespace App\Security\Voter;

use App\Entity\Hedge;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RoleVoter extends Voter
{
    const ROLE_TRADER = 'ROLE_TRADER';
    const ROLE_BOARD_MEMBER = 'ROLE_BOARD_MEMBER';
    const ROLE_RISK_CONTROLLER = 'ROLE_RISK_CONTROLLER';
    const ROLE_BU_HEDGING_COMMITTEE = 'ROLE_BU_HEDGING_COMMITTEE';
    const ROLE_BU_MEMBER = 'ROLE_BU_MEMBER';

    protected $attributes = [
        self::ROLE_TRADER,
        self::ROLE_BOARD_MEMBER,
        self::ROLE_RISK_CONTROLLER,
        self::ROLE_BU_HEDGING_COMMITTEE,
        self::ROLE_BU_MEMBER,
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

        if ($user->hasRole($attribute)) {
            return true;
        }

        return false;
    }
}
