<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HedgeAlertUserRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return array
     */
    public function findByUserOrderedByTimestamp(User $user): array
    {
        $qb = $this->createQueryBuilder('hau')
            ->leftJoin('hau.alert', 'a')
            ->where('hau.user = :user')
            ->andWhere('hau.deleted = :deleted')
            ->setParameter('user', $user)
            ->setParameter('deleted', 0)
            ->orderBy('a.timestamp', 'DESC');

        return $qb->getQuery()->getResult();
    }
}