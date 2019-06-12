<?php

namespace App\Repository;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\RMP;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{

    /**
     * @param RMP $rmp
     * @return array
     */
    public function findByRmp(RMP $rmp): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.businessUnits', 'bu')
            ->where('bu = :rmpBusinessUnit')
            ->orWhere('u.role = :roleRiskController')
            ->setParameter('rmpBusinessUnit', $rmp->getBusinessUnit())
            ->setParameter('roleRiskController', User::ROLE_RISK_CONTROLLER);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $roles
     * @param BusinessUnit $businessUnit
     * @param User|null $excludedUser
     * @return array
     */
    public function findByRolesAndBusinessUnit(array $roles, BusinessUnit $businessUnit, ?User $excludedUser = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.businessUnits', 'bu')
            ->where('u.role IN (:roles)')
            ->andWhere('bu = :businessUnit')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('enabled', 1)
            ->setParameter('roles', $roles)
            ->setParameter('businessUnit', $businessUnit);

        if ($excludedUser) {
            $qb->andWhere('u != :excludedUser')
                ->setParameter('excludedUser', $excludedUser);
        }

        return $qb->getQuery()->getResult();
    }
  
    /**
     * @param string $role
     * @param User|null $excludedUser
     * @return array
     */
    public function findByRole(string $role, ?User $excludedUser = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.businessUnits', 'bu')
            ->where('u.role = :role')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('enabled', 1)
            ->setParameter('role', $role);

        if ($excludedUser) {
            $qb->andWhere('u != :excludedUser')
                ->setParameter('excludedUser', $excludedUser);
        }

        return $qb->getQuery()->getResult();
    }
}