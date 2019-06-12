<?php

namespace App\Security\Voter;

use App\Entity\RMP;
use App\Entity\RmpValidation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RmpVoter extends Voter
{
    const RMP_EDIT = 'rmp_edit';
    const RMP_BLOCK = 'rmp_block';
    const RMP_APPROVAL_REQUEST = 'rmp_approval_request';
    const RMP_VALIDATE = 'rmp_validate';
    const RMP_CANCEL = 'rmp_cancel';
    const RMP_VIEW = 'rmp_view';
    const RMP_DRAFT = 'rmp_draft';

    protected $attributes = [
        self::RMP_EDIT,
        self::RMP_BLOCK,
        self::RMP_APPROVAL_REQUEST,
        self::RMP_VALIDATE,
        self::RMP_CANCEL,
        self::RMP_VIEW,
        self::RMP_DRAFT
    ];

    protected $em;

    /**
     * Rmpvoter constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
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
            case self::RMP_EDIT:
                return $this->canEdit($subject, $user);
            case self::RMP_BLOCK:
                return $this->canBlock($subject, $user);
            case self::RMP_APPROVAL_REQUEST:
                return $this->canRequestApproval($subject, $user);
            case self::RMP_VALIDATE:
                return $this->canValidate($subject, $user);
            case self::RMP_CANCEL:
                return $this->canCancel($subject, $user);
            case self::RMP_VIEW:
                return $this->canView($user);
            case self::RMP_DRAFT:
                return $this->canDraft($subject, $user);
            default:
                return false;
        }
    }

    /**
     * @param RMP $rmp
     * @param User  $user
     *
     * @return bool
     */
    public function canEdit(RMP $rmp, User $user): bool
    {
        return $rmp->isDraft() && !$user->hasRole(User::ROLE_BU_MEMBER);
    }

    /**
     * @param RMP $rmp
     * @param User $user
     * @return bool
     */
    public function canBlock(RMP $rmp, User $user): bool
    {
        return $rmp->isApproved()
            && ($user->hasRole(User::ROLE_RISK_CONTROLLER)
                || ($user->hasRole(User::ROLE_BOARD_MEMBER) && $user->getBusinessUnits()->contains($rmp->getBusinessUnit())));
    }

    /**
     * @param RMP $rmp
     * @param User $user
     * @return bool
     */
    public function canRequestApproval(RMP $rmp, User $user): bool
    {
        return ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE) || $user->hasRole(User::ROLE_RISK_CONTROLLER))
            && RMP::STATUS_DRAFT == $rmp->getStatus();
    }

    /**
     *
     * @param RMP $rmp
     * @param User $user
     *
     * @return bool
     */
    public function canValidate(RMP $rmp, User $user): bool
    {
        $userRmpValidation = null;
        if ($user->hasRole(User::ROLE_BOARD_MEMBER)) {
            $userRmpValidation = $this->em->getRepository(RmpValidation::class)->findOneBy(['rmp' => $rmp, 'user' => $user, 'active' => 1]);
        }

        return ($user->hasRole(User::ROLE_RISK_CONTROLLER) && RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER == $rmp->getStatus())
            || ($user->hasRole(User::ROLE_BOARD_MEMBER) && RMP::STATUS_PENDING_APPROVAL_BOARD_MEMBER == $rmp->getStatus() && !$userRmpValidation instanceof RmpValidation);
    }

    /**
     * @param RMP $rmp
     * @param User $user
     * @return bool
     */
    public function canCancel(RMP $rmp, User $user): bool
    {
        return $rmp->isDraft() && ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE) || $user->hasRole(User::ROLE_RISK_CONTROLLER));
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canView(User $user): bool
    {
        return !$user->hasRole(User::ROLE_BU_MEMBER);
    }

    /**
     * @param RMP $rmp
     * @param User $user
     * @return bool
     */
    public function canDraft(RMP $rmp, User $user): bool
    {
        return ($user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE) || $user->hasRole(User::ROLE_RISK_CONTROLLER)) && ($rmp->isApproved() || $rmp->isArchived());
    }
}
