<?php

namespace App\Repository;

use App\Entity\TypeEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeEvent>
 *
 * @method TypeEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeEvent[]    findAll()
 * @method TypeEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeEvent::class);
    }
} 