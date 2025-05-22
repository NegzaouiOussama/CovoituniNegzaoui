<?php

namespace App\Repository;

use App\Entity\EventParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventParticipation>
 *
 * @method EventParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventParticipation[]    findAll()
 * @method EventParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventParticipation::class);
    }

    /**
     * Find participations by user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.event', 'e')
            ->andWhere('ep.utilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find participations by event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.utilisateur', 'u')
            ->andWhere('ep.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('ep.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find participations by conductor (driver who approved the participation)
     */
    public function findByConducteur(int $conducteurId): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.event', 'e')
            ->join('ep.utilisateur', 'u')
            ->andWhere('ep.conducteur = :conducteurId')
            ->setParameter('conducteurId', $conducteurId)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find all participations for a user, including as participant and as conductor
     */
    public function findAllForUser(int $userId): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.event', 'e')
            ->leftJoin('ep.utilisateur', 'u')
            ->leftJoin('ep.conducteur', 'c')
            ->andWhere('ep.utilisateur = :userId OR ep.conducteur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 