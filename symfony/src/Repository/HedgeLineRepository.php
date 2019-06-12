<?php

namespace App\Repository;

use App\Entity\Hedge;
use App\Entity\HedgeLine;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\HedgingTool;
use App\Entity\RMP;
use App\Entity\RmpSubSegment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HedgeLineRepository extends ServiceEntityRepository
{
    /**
     * @param RegistryInterface     $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HedgeLine::class);
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     * @return array
     */
    public function findRealizedByRmpSubSegment(RmpSubSegment $rmpSubSegment): array
    {
        $qb = $this->createQueryBuilder('hl')
            ->join('hl.hedge', 'h')
            ->where('hl.rmpSubSegment = :rmpSubSegment')
            ->andWhere('h.status = :status')
            ->setParameter('rmpSubSegment', $rmpSubSegment)
            ->setParameter('status', Hedge::STATUS_REALIZED);

        return $qb->getQuery()->getResult();
    }
}
