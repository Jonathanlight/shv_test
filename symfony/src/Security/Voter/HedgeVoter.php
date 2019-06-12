<?php

namespace App\Security\Voter;

use App\Entity\Hedge;
use App\Entity\RmpValidation;
use App\Entity\RMP;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class HedgeVoter extends Voter
{
    const HEDGE_CANCEL = 'hedge_cancel';
    const HEDGE_CREATE = 'hedge_create';
    const HEDGE_VALIDATE = 'hedge_validate';
    const HEDGE_WRITE_OFF = 'hedge_write_off';
    const HEDGE_GENERATE_BLOTTER = 'hedge_generate_blotter';
    const HEDGE_REQUEST_EXECUTION = 'hedge_request_execution';
    const HEDGE_TEST_GENERATOR = 'hedge_test_generator';
    const HEDGE_EDIT = 'hedge_edit';
    const HEDGE_SAVE = 'hedge_save';

    protected $attributes = [
        self::HEDGE_CANCEL,
        self::HEDGE_CREATE,
        self::HEDGE_VALIDATE,
        self::HEDGE_WRITE_OFF,
        self::HEDGE_GENERATE_BLOTTER,
        self::HEDGE_REQUEST_EXECUTION,
        self::HEDGE_TEST_GENERATOR,
        self::HEDGE_EDIT,
        self::HEDGE_SAVE,
    ];

    protected $session;

    protected $em;

    /**
     * HedgeVoter constructor.
     * @param SessionInterface $session
     * @param EntityManagerInterface $em
     */
    public function __construct(SessionInterface $session, EntityManagerInterface $em)
    {
        $this->session = $session;
        $this->em = $em;
    }

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
            case self::HEDGE_CANCEL:
                return $this->canCancel($subject, $user);
            case self::HEDGE_CREATE:
                return $this->canCreate($user);
            case self::HEDGE_VALIDATE:
                return $this->canValidate($subject, $user);
            case self::HEDGE_WRITE_OFF:
                return $this->canWriteOff($subject, $user);
            case self::HEDGE_GENERATE_BLOTTER:
                return $this->canGenerateBlotter($subject, $user);
            case self::HEDGE_REQUEST_EXECUTION:
                return $this->canRequestExecution($subject, $user);
            case self::HEDGE_TEST_GENERATOR:
                return $this->canTestGenerator($subject, $user);
            case self::HEDGE_EDIT:
                return $this->canEdit($subject, $user);
            case self::HEDGE_SAVE:
                return $this->canSave($subject, $user);
            default:
                return false;
        }
    }

    /**
     * @param Hedge|null $hedge
     * @param User $user
     * @return bool
     */
    public function canSave(?Hedge $hedge, User $user): bool
    {
        return ($hedge->isDraft() && ($user->hasRole(User::ROLE_TRADER) || $user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE)))
            || ($hedge->isPendingExecution() && $user->hasRole(User::ROLE_TRADER));
    }

    /**
     * @param Hedge|null $hedge
     * @param User $user
     * @return bool
     */
    public function canEdit(?Hedge $hedge, User $user): bool
    {
        return (!$hedge || $user->hasRole(User::ROLE_TRADER)
            || (($user->hasRole(User::ROLE_BOARD_MEMBER) || $user->hasRole(User::ROLE_RISK_CONTROLLER)) && !$hedge->isDraft())
            || (($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE))
                &&  $user->getBusinessUnits()->contains($hedge->getRmp()->getBusinessUnit())));
    }

    /**
     * @param Hedge $hedge
     * @param User  $user
     *
     * @return bool
     */
    public function canCancel(Hedge $hedge, User $user): bool
    {
        return ((($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE)) && !$hedge->isPendingCancelation())
             || ($user->hasRole(User::ROLE_TRADER)
                    && (Hedge::STATUS_PENDING_EXECUTION == $hedge->getStatus() || Hedge::STATUS_DRAFT == $hedge->getStatus())));
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function canCreate(User $user): bool
    {
        return $user->hasRole(User::ROLE_TRADER)
             || $user->hasRole(User::ROLE_BU_MEMBER)
             || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE);
    }
    /**
     *
     * @param Hedge $hedge
     * @param User $user
     *
     * @return bool
     */
    public function canValidate(Hedge $hedge, User $user): bool
    {
        return ($user->hasRole(User::ROLE_RISK_CONTROLLER) && Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER == $hedge->getStatus())
             || ($user->hasRole(User::ROLE_BOARD_MEMBER) && Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER == $hedge->getStatus());
    }

    /**
     * @param Hedge $hedge
     * @param User $user
     *
     * @return bool
     */
    public function canWriteOff(Hedge $hedge, User $user): bool
    {
        return ($user->hasRole(User::ROLE_TRADER) && Hedge::STATUS_PENDING_EXECUTION == $hedge->getStatus() && $hedge->isPartiallyRealized());
    }

    /**
     * @param Hedge $hedge
     * @param User $user
     * @return bool
     */
    public function canGenerateBlotter(Hedge $hedge, User $user): bool
    {
        return $user->hasRole(User::ROLE_TRADER) && Hedge::STATUS_PENDING_EXECUTION == $hedge->getStatus();
    }

    /**
     * @param Hedge $hedge
     * @param User $user
     * @return bool
     */
    public function canRequestExecution(Hedge $hedge, User $user): bool
    {
        return ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE) || $user->hasRole(User::ROLE_TRADER))
            && Hedge::STATUS_DRAFT == $hedge->getStatus();
    }

    public function canTestGenerator(Hedge $hedge, User $user)
    {
        return /*getenv('APP_ENV') == 'dev' && */Hedge::STATUS_PENDING_EXECUTION == $hedge->getStatus()
            && $user->hasRole(User::ROLE_TRADER);
    }
}
