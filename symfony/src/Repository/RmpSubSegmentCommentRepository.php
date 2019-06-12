<?php

namespace App\Repository;

use App\Entity\RMP;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class RmpSubSegmentCommentRepository extends EntityRepository
{

    /**
     * @param RMP $rmp
     * @return array
     */
    public function findByRmp(RMP $rmp): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.parent', 'rss')
            ->where('rss.rmp = :rmp')
            ->setParameter('rmp', $rmp);

        return $qb->getQuery()->getResult();
    }
}