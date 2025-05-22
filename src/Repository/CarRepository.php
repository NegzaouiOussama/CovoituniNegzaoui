<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 *
 * @method Car|null find($id, $lockMode = null, $lockVersion = null)
 * @method Car|null findOneBy(array $criteria, array $orderBy = null)
 * @method Car[]    findAll()
 * @method Car[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    /**
     * @return Car[] Returns an array of Car objects
     */
    public function findByUserId(int $userId): array
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.userId = :val')
                ->setParameter('val', $userId)
                ->orderBy('c.id', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            // Si la colonne userId n'existe pas encore, retourne un tableau vide
            return [];
        }
    }

    /**
     * Override de la méthode findBy pour gérer temporairement le cas où userId n'existe pas
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if (isset($criteria['userId'])) {
            try {
                return parent::findBy($criteria, $orderBy, $limit, $offset);
            } catch (\Exception $e) {
                // Si la colonne userId n'existe pas encore, retourne un tableau vide
                return [];
            }
        }
        
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
} 