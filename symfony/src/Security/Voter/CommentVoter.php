<?php

namespace App\Security\Voter;

use App\Entity\HedgeComment;
use App\Entity\RmpSubSegmentComment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentVoter extends Voter
{
    const COMMENT_DELETE = 'comment_delete';
    const COMMENT_EDIT = 'comment_edit';

    protected $attributes = [
        self::COMMENT_DELETE,
        self::COMMENT_EDIT,
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
            case self::COMMENT_DELETE:
                return $this->canDelete($subject, $user);
            case self::COMMENT_EDIT:
                return $this->canEdit($subject, $user);
            default:
                return false;
        }
    }

    /**
     * @param HedgeComment|RmpSubSegmentComment $comment
     * @param User  $user
     *
     * @return bool
     */
    public function canDelete($comment, User $user): bool
    {
       return $comment->getUser() === $user;
    }

    /**
     * @param HedgeComment|RmpSubSegmentComment $comment
     * @param User  $user
     *
     * @return bool
     */
    public function canEdit($comment, User $user): bool
    {
       return $comment->getUser() === $user;
    }
}
