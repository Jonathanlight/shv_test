<?php

namespace App\Repository;

use App\Entity\Pricer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Pricer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pricer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pricer[]    findAll()
 * @method Pricer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PricerRepository extends ServiceEntityRepository
{
    /**
     * PricerRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Pricer::class);
    }

    /**
     * @param $numberOfDays
     * @return mixed
     * @throws \Exception
     */
    public function findAllInNumberOfDaysAgo($numberOfDays) {

        $query = $this->createQueryBuilder('p')
            ->where('p.createdAt BETWEEN :relatif_date AND :today_date')
            ->setParameter('relatif_date', new \DateTime($numberOfDays . ' days ago'))
            ->setParameter('today_date', new \DateTime('now'))
            ->getQuery();

        return $query->execute();
    }

    /**
     * @param $numberOfDays
     * @return mixed
     * @throws \Exception
     */
    public function findAllOutdatedInNumberOfDays($numberOfDays) {

        $query = $this->createQueryBuilder('p')
            ->where('p.createdAt NOT BETWEEN :relatif_date AND :today_date')
            ->setParameter('relatif_date', new \DateTime($numberOfDays . ' days ago'))
            ->setParameter('today_date', new \DateTime('now'))
            ->getQuery();

        return $query->execute();
    }

    /**
     * @return array
     */
    public function findLastPricer(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }
}
