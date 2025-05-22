<?php

namespace App\Repository;

use App\Entity\ReponseAvis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReponseAvis>
 *
 * @method ReponseAvis|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReponseAvis|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReponseAvis[]    findAll()
 * @method ReponseAvis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReponseAvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseAvis::class);
    }

    /**
     * Find responses for a specific review
     *
     * @param int $avisId The ID of the review
     * @return ReponseAvis[] Returns an array of ReponseAvis objects
     */
    public function findByAvis(int $avisId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.avis = :avisId')
            ->setParameter('avisId', $avisId)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 