<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 *
 * @method ChatMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatMessage[]    findAll()
 * @method ChatMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    /**
     * Trouve tous les messages d'une conversation liée à une réservation
     */
    public function findByReservation(int $reservationId, string $type): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.reservationId = :reservationId')
            ->andWhere('m.reservationType = :type')
            ->setParameter('reservationId', $reservationId)
            ->setParameter('type', $type)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve tous les messages plus récents qu'un ID donné
     */
    public function findNewerThan(int $reservationId, string $type, int $lastId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.reservationId = :reservationId')
            ->andWhere('m.reservationType = :type')
            ->andWhere('m.id > :lastId')
            ->setParameter('reservationId', $reservationId)
            ->setParameter('type', $type)
            ->setParameter('lastId', $lastId)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Marque tous les messages comme lus pour un utilisateur donné
     */
    public function markAsRead(int $reservationId, string $type, int $userId): void
    {
        $now = new \DateTimeImmutable();
        
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.readAt', ':now')
            ->where('m.reservationId = :reservationId')
            ->andWhere('m.reservationType = :type')
            ->andWhere('m.receiverId = :userId')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('reservationId', $reservationId)
            ->setParameter('type', $type)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
    
    /**
     * Trouve toutes les conversations d'un utilisateur
     */
    public function findConversations(int $userId): array
    {
        // Trouver les derniers messages par conversation (réservation)
        $qb = $this->createQueryBuilder('m1');
        
        $subQb = $this->createQueryBuilder('m2')
            ->select('MAX(m2.id)')
            ->where('m2.reservationId = m1.reservationId')
            ->andWhere('m2.reservationType = m1.reservationType');
            
        return $qb->where($qb->expr()->in('m1.id', $subQb->getDQL()))
            ->andWhere('(m1.senderId = :userId OR m1.receiverId = :userId)')
            ->setParameter('userId', $userId)
            ->orderBy('m1.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Compte le nombre de messages non lus pour un utilisateur
     */
    public function countUnreadMessages(int $userId): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.receiverId = :userId')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
} 