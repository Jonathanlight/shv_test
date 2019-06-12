<?php

namespace App\Repository;

use App\Entity\Hedge;
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

class HedgeRepository extends ServiceEntityRepository
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
        parent::__construct($registry, Hedge::class);
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param RmpSubSegment $rmpSubSegment
     * @param array $statuses
     * @param int $riskLevel
     * @return array
     */
    public function findByRmpSubSegmentAndStatuses(RmpSubSegment $rmpSubSegment, array $statuses, int $riskLevel): array
    {
        $qb = $this->createQueryBuilder('h')
            ->join('h.hedgeLines', 'hedgeLines')
            ->join('h.hedgingTool', 'hedgingTool')
            ->where('hedgeLines.rmpSubSegment = :rmpSubSegment')
            ->andWhere('h.status IN (:status)')
            ->setParameter('rmpSubSegment', $rmpSubSegment)
            ->setParameter('status', $statuses);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return array
     */
    public function findByBusinessUnit(BusinessUnit $businessUnit): array
    {
        $qb = $this->createQueryBuilder('h')
            ->join('h.rmp', 'rmp')
            ->where('rmp.businessUnit = :businessUnit')
            ->setParameter('businessUnit', $businessUnit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $businessUnits
     * @param array $statuses
     * @return array
     */
    public function findByBusinessUnitsAndStatuses(array $businessUnits, array $statuses): array
    {
        $qb = $this->createQueryBuilder('h')
            ->join('h.rmp', 'rmp')
            ->where('rmp.businessUnit IN (:businessUnits)')
            ->andWhere('h.status IN (:statuses)')
            ->setParameter('businessUnits', $businessUnits)
            ->setParameter('statuses', $statuses);

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
    public function list(array $filters, array $orderBy = [], $limit, $offset)
    {
        $filtersQuery = [];

        $user = $this->tokenStorage->getToken()->getUser();

        foreach ($filters as $filter) {
            if ('e.status' == $filter['name'] && (Hedge::STATUS_ALL == $filter['value'] || $filter['value'] == '')) {
                if ($user->hasRole(User::ROLE_RISK_CONTROLLER) || $user->hasRole(User::ROLE_BOARD_MEMBER)) {
                    $filtersQuery['customAllStatus'] = true;
                }
                continue;
            }
            if ($filter['value'] || $filter['value'] == '0') {
                $filtersQuery[$filter['name']][] = $filter['value'];
            }
        }

        if (!isset($filtersQuery['rmp.businessUnit'])) {
            if ($user->hasRole(User::ROLE_BU_MEMBER) || $user->hasRole(User::ROLE_BU_HEDGING_COMMITTEE)) {
                $selectedBusinessUnit = $this->session->get('selectedBusinessUnit') ?: $user->getBusinessUnits()->first();
                $filtersQuery['rmp.businessUnit'] = [$selectedBusinessUnit->getId()];
            } elseif ($user->hasRole(User::ROLE_BOARD_MEMBER)) {
                foreach ($user->getBusinessUnits()  as $businessUnit) {
                    $filtersQuery['rmp.businessUnit'][] = $businessUnit->getId();
                }
            }
        }

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.rmp', 'rmp')
            ->leftJoin('e.subSegment', 'subSegment')
            ->leftJoin('e.hedgingTool', 'hedgingTool')
            ->leftJoin('e.firstMaturity', 'firstMaturity')
            ->leftJoin('e.lastMaturity', 'lastMaturity')
            ->leftJoin('rmp.businessUnit', 'businessUnit')
            ->leftJoin('subSegment.segment', 'segment');

        foreach ($filtersQuery as $filter => $value) {
            switch ($filter) {
                case 'maturity_from':
                    $dateFilterFormatted = date('Y-m-d', strtotime('01-'.$value[0]));
                    $qb->andWhere('firstMaturity.date >= :firstMaturity')
                        ->setParameter('firstMaturity', $dateFilterFormatted);
                    break;
                case 'maturity_to':
                    $dateFilterFormatted = date('Y-m-d', strtotime('01-'.$value[0]));
                    $qb->andWhere('lastMaturity.date <= :lastMaturity')
                        ->setParameter('lastMaturity', $dateFilterFormatted);
                    break;
                case 'e.product':
                    $qb->andWhere($filter.'1 IN (:product) OR '.$filter.'2 IN (:product)')
                        ->setParameter('product', implode(',', $value));
                    break;
                case 'flags':
                    foreach ($value as $flag) {
                        if ($flag == Hedge::FILTER_FLAG_PARTIALLY_REALIZED) {
                            $qb->andWhere('e.partiallyRealized = :partiallyRealized')
                                ->setParameter('partiallyRealized', true);
                        } else if ($flag == Hedge::FILTER_FLAG_EXTRA_APPROVAL) {
                            $qb->andWhere('e.extraApproval = :extraApproval')
                                ->setParameter('extraApproval', true);
                        }
                    }
                    break;
                case 'e.id':
                    $qb->andWhere($qb->expr()->like('e.id', ':id'))
                        ->setParameter('id', $value[0] . '%');
                    break;
                case 'customAllStatus':
                    $qb->andWhere('e.status != :draftStatus')
                        ->setParameter('draftStatus', Hedge::STATUS_DRAFT);
                    break;
                default:
                    $qb->andWhere($filter.' IN (:'.explode('.', $filter)[1].')')
                        ->setParameter(explode('.', $filter)[1], $value);
            }
        }

        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy($field, $order);
        }

        $qb->addOrderBy('e.orderDate', 'DESC');
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        $paginator = new Paginator($qb);

        return $paginator;
    }

    /**
     * @param int $interval
     * @return array
     */
    public function findRemindable(int $interval)
    {
        $date = new \DateTime();
        $date->modify('-' . $interval . ' days');

        $qb = $this->createQueryBuilder('h')
            ->where('h.status IN (:statuses)')
            ->andWhere('h.updatedAt < :date')
            ->setParameter('statuses', [Hedge::STATUS_PENDING_APPROVAL_RISK_CONTROLLER, Hedge::STATUS_PENDING_APPROVAL_BOARD_MEMBER, Hedge::STATUS_PENDING_EXECUTION])
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array|null $businessUnits
     * @return array
     */
    public function findLastHedgeUpdated(array $businessUnits = null): array
    {
        $qb = $this->createQueryBuilder('h')
                    ->join('h.rmp', 'rmp')
                    ->where('h.status NOT IN (:statuses)')
                    ->setParameter('statuses',[Hedge::STATUS_CANCELED, Hedge::STATUS_DRAFT])
                    ->orderBy('h.updatedAt', 'DESC')
                    ->setMaxResults(1);

        if ($businessUnits) {
            $qb->andWhere('rmp.businessUnit IN (:businessUnits)')
                ->setParameter('businessUnits', $businessUnits);
        }

        return $qb->getQuery()->getResult();
    }
}
