<?php

namespace App\Repository;

use App\Entity\Hedge;
use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\Maturity;
use App\Entity\MasterData\SubSegment;
use App\Entity\RMP;
use App\Service\MaturityManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MaturityRepository extends ServiceEntityRepository
{
    private $maturityManager;

    /**
     * MaturityRepository constructor.
     * @param RegistryInterface     $registry
     * @param MaturityManager $maturityManager
     */
    public function __construct(RegistryInterface $registry, MaturityManager $maturityManager)
    {
        parent::__construct($registry, Maturity::class);
        $this->maturityManager = $maturityManager;
    }

    /**
     * @param RMP $rmp
     * @return QueryBuilder
     */
    public function queryByRmp(RMP $rmp): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.year >= :rmpYear');
        if ($rmp->getValidityPeriod() == date('Y')) {
            $qb->andWhere('m.month >= :currentMonth AND m.year = :rmpYear')
                ->orWhere('m.year > :rmpYear')
                ->setParameter('currentMonth', date('m'));
        }
        $qb->setParameter('rmpYear', $rmp->getValidityPeriod())
            ->orderBy('m.year', 'ASC')
            ->addOrderBy('m.month', 'ASC');

        return $qb;
    }

    /**
     * @param RMP $rmp
     * @param Hedge $hedge
     * @return QueryBuilder
     */
    public function queryByRmpAndHedge(RMP $rmp, Hedge $hedge): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m');
        $firstMaturity = $hedge->getFirstMaturity();

        if ($firstMaturity && $hedge->getStatus() != Hedge::STATUS_DRAFT) {
            $qb->andWhere('m.month >= :month AND m.year = :year')
                ->orWhere('m.year > :year')
                ->setParameter('month', $firstMaturity->getMonth())
                ->setParameter('year', $firstMaturity->getYear());
        } else {
            if ($rmp->getValidityPeriod() == date('Y')) {
                $qb->andWhere('m.month >= :currentMonth AND m.year = :rmpYear')
                    ->orWhere('m.year > :rmpYear')
                    ->setParameter('rmpYear', $rmp->getValidityPeriod())
                    ->setParameter('currentMonth', date('m'));
            } else {
                $qb->andWhere('m.year >= :rmpYear')
                    ->setParameter('rmpYear', $rmp->getValidityPeriod());
            }
            $qb->orderBy('m.year', 'ASC')
                ->addOrderBy('m.month', 'ASC');
        }

        return $qb;
    }

    /**
     * @param Maturity $firstMaturity
     * @param Maturity $lastMaturity
     * @return QueryBuilder
     */
    public function queryMaturityRange(Maturity $firstMaturity, Maturity $lastMaturity): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.date >= :dateFirstMaturity')
            ->andWhere('m.date <= :dateLastMaturity')
            ->setParameter('dateFirstMaturity', $firstMaturity->getDate())
            ->setParameter('dateLastMaturity', $lastMaturity->getDate())
            ->orderBy('m.date', 'ASC');

        return $qb;
    }

    /**
     * @param Maturity $maturity
     * @param BusinessUnit $businessUnit
     * @param RMP $rmp
     * @return array
     */
    public function findByMaturityAndBusinessUnit(Maturity $maturity, BusinessUnit $businessUnit, RMP $rmp): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.month >= :maturityMonth AND m.year = :maturityYear')
            ->orWhere('m.year > :maturityYear')
            ->setParameter('maturityMonth', $maturity->getMonth())
            ->setParameter('maturityYear', $maturity->getYear())
            ->orderBy('m.year')
            ->addOrderBy('m.month');

        $qb->join('App\Entity\RMP', 'rmp', 'WITH', 'rmp.validityPeriod = m.year and rmp.businessUnit = :businessUnit')
            ->select('m, rmp.id')
            ->setParameter('businessUnit', $businessUnit);

        return $this->formatResult($qb->getQuery()->getResult(), $rmp);
    }

    /**
     * @return array
     */
    public function findFromNow(): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.year >= :currentYear')
            ->andWhere('m.month >= :currentMonth')
            ->setParameter('currentYear', date('Y'))
            ->setParameter('currentMonth', date('m'))
            ->orderBy('m.year', 'ASC')
            ->addOrderBy('m.month', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Maturity|null
     */
    public function findFirstMaturity(): ?Maturity
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.month >= :currentMonth AND m.year = :currentYear')
            ->setParameter('currentMonth', date('m'))
            ->setParameter('currentYear', date('Y'))
            ->orderBy('m.year', 'ASC')
            ->addOrderBy('m.month', 'ASC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Maturity $firstMaturity
     * @param Maturity $lastMaturity
     * @param RMP $rmp
     * @param bool $formatted
     * @return array
     */
    public function findMaturitiesRange(Maturity $firstMaturity, Maturity $lastMaturity, RMP $rmp, $formatted = true): array
    {
        $qb = $this->queryMaturityRange($firstMaturity, $lastMaturity);
        if ($formatted) {
            return $this->formatResult($qb->getQuery()->getResult());
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    /**
     * @param Maturity $firstMaturity
     * @param Maturity $lastMaturity
     * @param BusinessUnit $businessUnit
     * @param SubSegment $subSegment
     * @param RMP $rmp
     * @return array
     */
    public function findMaturitiesRangeWithRmpSubSegment(Maturity $firstMaturity, Maturity $lastMaturity, BusinessUnit $businessUnit, SubSegment $subSegment, RMP $rmp): array
    {
        $qb = $this->queryMaturityRange($firstMaturity, $lastMaturity);
        $qb->join('App\Entity\RMP', 'rmp', 'WITH', 'rmp.validityPeriod = m.year and rmp.businessUnit = :businessUnit and (rmp.status = :statusApproved or (rmp.status = :statusArchived and rmp.archivedAutomatically = :archivedAutomatically))')
           ->leftJoin('App\Entity\RmpSubSegment', 'rmpSubSegment', 'WITH', 'rmpSubSegment.rmp = rmp and rmpSubSegment.subSegment = :subSegment')
            ->select('m, rmpSubSegment.id, rmp.validityPeriod')
            ->setParameter('subSegment', $subSegment)
            ->setParameter('statusApproved', RMP::STATUS_APPROVED)
            ->setParameter('statusArchived', RMP::STATUS_ARCHIVED)
            ->setParameter('archivedAutomatically', 1)
            ->setParameter('businessUnit', $businessUnit);

        return $this->formatResult($qb->getQuery()->getResult(), $rmp);
    }

    /**
     * @param RMP $rmp
     * @param BusinessUnit $businessUnit
     * @return array
     */
    public function findByRmpAndBusinessUnit(RMP $rmp, BusinessUnit $businessUnit): array
    {
        $qb = $this->queryByRmp($rmp);
        $qb->join('App\Entity\RMP', 'rmp', 'WITH', 'rmp.validityPeriod = m.year and rmp.businessUnit = :businessUnit')
                ->select('m, rmp.id')
                ->setParameter('businessUnit', $businessUnit);

        return $this->formatResult($qb->getQuery()->getResult(), $rmp);
    }

    /**
     * @param array $results
     * @param RMP|null $rmp
     * @return array
     */
    private function formatResult(array $results, RMP $rmp = null): array
    {
        $formattedResults = [];

        foreach ($results as $k => $result) {
            $maturity = $result[0];
            $formattedResults[] = ['id' =>$maturity->getId(), 'name' => $maturity->__toString()];

            $formattedResults[$k]['interval'] = $this->maturityManager->getMaturityIntervalByRmp($maturity);

            if (isset($result['id'])) {
                $formattedResults[$k]['rmpSubSegmentId'] = $result['id'];
            }
            if (isset($result['validityPeriod'])) {
                $formattedResults[$k]['validityPeriod'] = $result['validityPeriod'];
            }
        }

        return $formattedResults;
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function findOneByDate(\DateTime $date)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.date LIKE :date')
            ->setParameter('date', $date->format('Y-m') . '%');

        return $qb->getQuery()->getOneOrNullResult();
    }
}
