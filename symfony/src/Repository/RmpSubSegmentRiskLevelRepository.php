<?php

namespace App\Repository;

use App\Entity\RmpSubSegment;
use Doctrine\ORM\EntityRepository;

class RmpSubSegmentRiskLevelRepository extends EntityRepository
{
    /**
     * @param RmpSubSegment $rmpSubSegment
     * @return mixed
     */
    public function findByRmpSubSegment(RmpSubSegment $rmpSubSegment)
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.rmpSubSegment = :rmpSubSegment')
            ->setParameter('rmpSubSegment', $rmpSubSegment);

        return $qb->getQuery()->getResult();
    }
}