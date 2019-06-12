<?php

namespace App\Repository;

use App\Entity\Rmp;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class RmpValidationRepository extends EntityRepository
{
    /**
     * @param Rmp $rmp
     * @return array
     */
    public function findActivesByRmp(RMP $rmp): array
    {
        $qb = $this->createQueryBuilder('hv')
            ->where('hv.rmp = :rmp')
            ->andWhere('hv.active = :active')
            ->setParameter('rmp', $rmp)
            ->setParameter('active', 1);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Rmp $rmp
     */
    public function disablePreviousValidations(RMP $rmp)
    {
        $qb = $this->createQueryBuilder('hv')
            ->update()
            ->set('hv.active', ':inactive')
            ->where('hv.active = :active')
            ->andWhere('hv.rmp = :rmp')
            ->setParameter('inactive', 0)
            ->setParameter('active', 1)
            ->setParameter('rmp', $rmp);

        $qb->getQuery()->execute();
    }

}
