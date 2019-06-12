<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RmpAlertUserRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return array
     */
    public function findByUserOrderedByTimestamp(User $user): array
    {
        $qb = $this->createQueryBuilder('rau')
            ->leftJoin('rau.alert', 'a')
            ->where('rau.user = :user')
            ->andWhere('rau.deleted = :deleted')
            ->setParameter('user', $user)
            ->setParameter('deleted', 0)
            ->orderBy('a.timestamp', 'DESC');

        return $qb->getQuery()->getResult();
    }
}