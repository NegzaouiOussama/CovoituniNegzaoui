<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns an array of active events
     */
    public function findActiveEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'ACTIVE')
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of upcoming events
     */
    public function findUpcomingEvents(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.dateEvent >= :now')
            ->setParameter('status', 'ACTIVE')
            ->setParameter('now', $now->format('Y-m-d'))
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of past events
     */
    public function findPastEvents(): array
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateEvent < :today')
            ->setParameter('today', $today)
            ->orderBy('e.dateEvent', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 