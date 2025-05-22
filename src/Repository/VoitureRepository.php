<?php

namespace App\Repository;

use App\Entity\Car;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Alias pour CarRepository afin de satisfaire les dépendances dans le contrôleur
 * 
 * @extends ServiceEntityRepository<Car>
 */
class VoitureRepository extends CarRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }
} 