<?php

namespace App\Repository;

use App\Entity\MasterData\BusinessUnit;
use App\Entity\MasterData\Maturity;
use App\Entity\RMP;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RmpRepository extends ServiceEntityRepository
{
    private $session;

    private $tokenStorage;

    /**
     * @param RegistryInterface     $registry
     * @param SessionInterface      $session
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(RegistryInterface $registry, SessionInterface $session, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($registry, RMP::class);
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param BusinessUnit $businessUnit
     * @return array
     */
    public function findByApprovedAndBusinessUnit(BusinessUnit $businessUnit): array
    {
        $qb = $this->queryByBusinessUnit($businessUnit);

        $qb->andWhere('rmp.status = :status')
           ->setParameter('status', RMP::STATUS_APPROVED);

        $qb->andWhere("rmp.validityPeriod >= :validityPeriod");
        $qb->setParameter('validityPeriod', date('Y'));


        return $qb->getQuery()->getResult();
    }

    /**
     * @param BusinessUnit $businessUnit
     * @return QueryBuilder
     */
    public function queryByBusinessUnit(BusinessUnit $businessUnit): QueryBuilder
    {
        $qb = $this->createQueryBuilder('rmp')
            ->where('rmp.businessUnit = :selectedBusinessUnit')
            ->andWhere('rmp.validityPeriod >= :currentYear')
            ->orderBy('rmp.validityPeriod', 'ASC')
            ->setParameter('selectedBusinessUnit', $businessUnit)
            ->setParameter('currentYear', date('Y'));

        return $qb;
    }

    /**
     * @param BusinessUnit $businessUnit
     * @param Maturity $maturity
     * @return RMP|null
     */
    public function findByBusinessUnitAndMaturity(BusinessUnit $businessUnit, Maturity $maturity): ?RMP
    {
        $qb = $this->createQueryBuilder('rmp')
            ->where('rmp.businessUnit = :selectedBusinessUnit')
            ->andWhere('rmp.validityPeriod = :maturityYear')
            ->orderBy('rmp.validityPeriod', 'ASC')
            ->setParameter('selectedBusinessUnit', $businessUnit)
            ->setParameter('maturityYear', $maturity->getYear());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $businessUnits
     * @return RMP|null
     */
    public function findFirstByBusinessUnits(array $businessUnits): ?RMP
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.businessUnit IN (:businessUnits)')
            ->andWhere('r.status = :status')
            ->andWhere('r.validityPeriod >= :validityPeriod')
            ->setParameter('businessUnits', $businessUnits)
            ->setParameter('status', RMP::STATUS_APPROVED)
            ->setParameter('validityPeriod', date('Y'))
            ->setMaxResults(1)
            ->orderBy('r.validityPeriod', 'ASC');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array
     */
    public function findAllApprovedFromNow(): array
    {
        $qb = $this->createQueryBuilder('rmp')
            ->where('rmp.validityPeriod >= :currentYear')
            ->andWhere('rmp.status = :status')
            ->setParameter('status', RMP::STATUS_APPROVED)
            ->orderBy('rmp.validityPeriod', 'ASC')
            ->addOrderBy('rmp.name', 'ASC');

        $qb->setParameter('currentYear', date('Y'));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $filters
     * @param array $orderBy
     * @param $limit
     * @param $offset
     *
     * @return Paginator
     */
    public function list(array $filters, array $orderBy, $limit, $offset)
    {
        $filtersQuery = [];

        $user = $this->tokenStorage->getToken()->getUser();

        foreach ($filters as $filter) {
            if ('e.status' == $filter['name'] && RMP::STATUS_ALL == $filter['value']) {
                continue;
            }
            if ($filter['value'] || $filter['value'] == '0') {
                $filtersQuery[$filter['name']][] = $filter['value'];
            }
        }

        if (!isset($filtersQuery['e.businessUnit'])) {
            if ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE)) {
                $selectedBusinessUnit = $this->session->get('selectedBusinessUnit') ?: $user->getBusinessUnits()->first();
                $filtersQuery['e.businessUnit'] = [$selectedBusinessUnit->getId()];
            } elseif ($user->hasRole(User::ROLE_BOARD_MEMBER)) {
                foreach ($user->getBusinessUnits()  as $businessUnit) {
                    $filtersQuery['e.businessUnit'][] = $businessUnit->getId();
                }
            }
        }

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.businessUnit', 'businessUnit');

        foreach ($filtersQuery as $filter => $value) {
            switch ($filter) {
                case 'e.name':
                    $qb->andWhere($filter. ' LIKE :name')
                        ->setParameter('name', '%'.$value[0].'%');
                    break;
                case 'flags':
                    $whereFlags = '';
                    foreach ($value as $k => $flag) {
                        if ($k >= 1) {
                            $whereFlags .= ' OR ';
                        }
                        if ($flag == RMP::FILTER_FLAG_APPROVED_AUTOMATICALLY) {
                            $whereFlags .= ' e.approvedAutomatically = :approvedAutomatically';
                            $qb->setParameter('approvedAutomatically', true);
                        } else if ($flag == RMP::FILTER_FLAG_BLOCKED) {
                            $whereFlags .= ' e.blocked = :blocked';
                            $qb->setParameter('blocked', true);
                        } else if ($flag == RMP::FILTER_FLAG_AMENDMENT) {
                            $whereFlags .= ' e.amendment = :amendment';
                            $qb->setParameter('amendment', true);
                        }
                    }
                    $qb->andWhere($whereFlags);
                    break;
                default:
                    $qb->andWhere($filter.' IN (:'.explode('.', $filter)[1].')')
                        ->setParameter(explode('.', $filter)[1], $value);
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy($field, $order);
            }
        }

        $qb->addOrderBy('e.updatedAt', 'DESC');
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        $paginator = new Paginator($qb);

        return $paginator;
    }

    /**
     * @param RMP $rmp
     * @return RMP|null
     */
    public function findNextByRmp(RMP $rmp): ?RMP
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.validityPeriod = :validityPeriod')
            ->andWhere('r.status = :status')
            ->andWhere('r.businessUnit = :businessUnit')
            ->setParameter('validityPeriod', $rmp->getValidityPeriod()+1)
            ->setParameter('status', RMP::STATUS_APPROVED)
            ->setParameter('businessUnit', $rmp->getBusinessUnit());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param RMP $rmp
     * @return array
     */
    public function findForHistory(RMP $rmp): array
    {
        $businessUnit = $rmp->getBusinessUnit();
        $validityPeriod = $rmp->getValidityPeriod();

        $qb = $this->createQueryBuilder('r')
            ->where('r.businessUnit = :businessUnit')
            ->andWhere('r.validityPeriod = :validityPeriod')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('businessUnit', $businessUnit)
            ->setParameter('validityPeriod', $validityPeriod)
            ->setParameter('statuses', [RMP::STATUS_ARCHIVED, RMP::STATUS_APPROVED])
            ->orderBy('r.version', 'DESC');

        $results = $qb->getQuery()->getResult();

        return $results;
    }

    /**
     * @param RMP $rmp
     * @return array
     */
    public function findNextApprovedAutomatically(RMP $rmp): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.businessUnit = :businessUnit')
            ->andWhere('r.validityPeriod = :validityPeriod')
            ->andWhere('r.approvedAutomatically = :approvedAutomatically')
            ->andWhere('r.status = :statusApproved')
            ->setParameter('businessUnit', $rmp->getBusinessUnit())
            ->setParameter('validityPeriod', $rmp->getValidityPeriod()+1)
            ->setParameter('approvedAutomatically', 1)
            ->setParameter('statusApproved', RMP::STATUS_APPROVED)
            ->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return RMP|null
     */
    public function findLastUpdated(): ?RMP
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status NOT IN (:statuses)')
            ->andWhere('r.validityPeriod = :validityPeriod')
            ->setParameter('statuses', [RMP::STATUS_DRAFT, RMP::STATUS_ARCHIVED])
            ->setParameter('validityPeriod', date('Y'))
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults(1);

        $results = $qb->getQuery()->getResult();

        return isset($results[0]) ? $results[0] : null;
    }

    /**
     * @param array $statuses
     * @param array|null $businessUnits
     * @return array
     */
    public function findByStatusesFromNow(array $statuses, ?array $businessUnits = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->andWhere('r.validityPeriod >= :currentYear')
            ->setParameter('statuses', $statuses)
            ->setParameter('currentYear', date('Y'));

        if ($businessUnits) {
            $qb->andWhere('r.businessUnit IN (:businessUnits)')
                ->setParameter('businessUnits', $businessUnits);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function findForTraderHedgingRequests(): array
    {
        $qb = $this->createQueryBuilder('rmp')
            ->where('rmp.validityPeriod >= :lastYear')
            ->andWhere('rmp.status = :statusApproved')
            ->orWhere('rmp.status = :statusArchived AND rmp.archivedAutomatically = :archivedAutomatically')
            ->setParameter('lastYear', date('Y', strtotime('-1 year')))
            ->setParameter('statusApproved', RMP::STATUS_APPROVED)
            ->setParameter('statusArchived', RMP::STATUS_ARCHIVED)
            ->setParameter('archivedAutomatically', 1)
            ->orderBy('rmp.validityPeriod', 'ASC')
            ->addOrderBy('rmp.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $validityPeriods
     * @param BusinessUnit $businessUnit
     * @return array
     */
    public function findByValidityPeriodsAndBusinessUnit(array $validityPeriods, BusinessUnit $businessUnit): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.validityPeriod IN (:validityPeriods)')
            ->andWhere('r.businessUnit = :businessUnit')
            ->andWhere('r.status = :statusApproved or (r.status = :statusArchived and r.archivedAutomatically = :archivedAutomatically)')
            ->setParameter('validityPeriods', $validityPeriods)
            ->setParameter('businessUnit', $businessUnit)
            ->setParameter('statusApproved', RMP::STATUS_APPROVED)
            ->setParameter('statusArchived', RMP::STATUS_ARCHIVED)
            ->setParameter('archivedAutomatically', 1);

         return $qb->getQuery()->getResult();
     }

    /**
     * @param int $interval
     * @return array
     */
    public function findRemindable(int $interval)
    {
        $date = new \DateTime();
        $date->modify('-' . $interval . ' days');

        $qb = $this->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->andWhere('r.updatedAt < :date')
            ->setParameter('statuses', [RMP::STATUS_PENDING_APPROVAL_RISK_CONTROLLER, RMP::STATUS_PENDING_APPROVAL_BOARD_MEMBER])
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }
}
