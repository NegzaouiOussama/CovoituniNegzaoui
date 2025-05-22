<?php

namespace App\Repository;

use App\Entity\AnnonceEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnnonceEvent>
 *
 * @method AnnonceEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnnonceEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnnonceEvent[]    findAll()
 * @method AnnonceEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnonceEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnonceEvent::class);
    }

    /**
     * @return AnnonceEvent[] Returns an array of active AnnonceEvent objects
     */
    public function findActiveAnnouncements(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.departureDate > :now')
            ->setParameter('status', 'ouvert')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.departureDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AnnonceEvent[] Returns an array of AnnonceEvent objects for a specific driver
     */
    public function findByDriver(int $driverId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.driverId = :user')
            ->setParameter('user', $driverId)
            ->orderBy('a.departureDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AnnonceEvent[] Returns an array of AnnonceEvent objects for a specific event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('a.departureDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 