<?php

namespace App\Service;

use App\Entity\Hedge;
use App\Entity\HedgeComment;
use App\Entity\HedgeLine;
use App\Entity\MasterData\HedgingTool;
use App\Entity\MasterData\Product;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\RmpSubSegmentComment;
use App\Entity\RmpSubSegmentRiskLevel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class CommentManager
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Hedge|RmpSubSegment $parent
     * @param string $message
     * @param User $user
     *
     * @return HedgeComment|RmpSubSegmentComment
     */
    public function createComment($parent, string $message, User $user)
    {
        $comment = null;

        if (!empty($message)) {
            $parentClass = get_class($parent);
            if (class_exists($parentClass)) {
                $commentClass = $parentClass.'Comment';
                $comment = new $commentClass();

                $currentDateTime = new \DateTime();
                $comment->setParent($parent);
                $comment->setTimestamp($currentDateTime);
                $comment->setUpdatedAt($currentDateTime);
                $comment->setUser($user);
                $comment->setMessage($message);

                $this->em->persist($comment);
                $this->em->flush();
            }
        }

        return $comment;
    }
}