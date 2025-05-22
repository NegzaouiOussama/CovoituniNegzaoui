<?php

namespace App\Repository;

use App\Entity\Trajet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trajet>
 *
 * @method Trajet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trajet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trajet[]    findAll()
 * @method Trajet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrajetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trajet::class);
    }

    /**
     * @return Trajet[] Returns an array of Trajet objects ordered by creation date
     */
    public function findAllOrderedByDepartureDate(string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Trajet[] Returns an array of Trajet objects for specific driver
     */
    public function findByDriverId(int $driverId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.annonces', 'a')
            ->andWhere('a.driver_id = :driverId')
            ->setParameter('driverId', $driverId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Trajet[] Returns all available trips
     */
    public function findUpcomingTrips(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 