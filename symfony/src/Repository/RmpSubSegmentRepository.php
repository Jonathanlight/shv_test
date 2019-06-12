<?php

namespace App\Repository;

use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use Doctrine\ORM\EntityRepository;

class RmpSubSegmentRepository extends EntityRepository
{
    /**
     * @param RmpSubSegment $rmpSubSegment
     * @param RMP $rmp
     * @return mixed
     */
    public function findLastYearRmpSubSegment(RmpSubSegment $rmpSubSegment, RMP $rmp)
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.subSegment = :subSegment')
            ->andWhere('r.rmp = :rmp')
            ->setParameter('subSegment', $rmpSubSegment->getSubSegment())
            ->setParameter('rmp', $rmp)
            ->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }
}