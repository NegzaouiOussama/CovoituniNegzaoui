<?php

namespace App\Repository;

use App\Entity\Annonce;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Annonce>
 *
 * @method Annonce|null find($id, $lockMode = null, $lockVersion = null)
 * @method Annonce|null findOneBy(array $criteria, array $orderBy = null)
 * @method Annonce[]    findAll()
 * @method Annonce[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnonceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annonce::class);
    }

    /**
     * @return Annonce[] Returns an array of latest open announcements
     */
    public function findLatestOpen(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'ouvert')
            ->orderBy('a.date_publication', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Annonce[] Returns an array of Annonce objects for a specific driver
     */
    public function findByDriver(int $driverId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.trajet', 't')
            ->where('a.driver_id = :user')
            ->setParameter('user', $driverId)
            ->orderBy('a.departureDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * @return Annonce[] Returns an array of active Annonce objects
     */
    public function findActiveAnnouncements(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.trajet', 't')
            ->andWhere('a.status = :status')
            ->andWhere('a.departureDate > :now')
            ->setParameter('status', 'ouvert')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.departureDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the most recent announcements
     * 
     * @param int $limit The maximum number of announcements to return
     * @return Annonce[] The most recent announcements
     */
    public function findRecent(int $limit = 5): array
    {
        try {
            return $this->createQueryBuilder('a')
                ->orderBy('a.date_publication', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Count announcements by status
     * 
     * @param string $status The status to count
     * @return int The number of announcements with the given status
     */
    public function countByStatus(string $status): int
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Count announcements created today
     * 
     * @return int The number of announcements created today
     */
    public function countCreatedToday(): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.date_publication >= :today')
                ->andWhere('a.date_publication < :tomorrow')
                ->setParameter('today', $today)
                ->setParameter('tomorrow', $tomorrow)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
} 