<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }
    
    // Add custom query methods here if needed
    
    /**
     * Count reclamations by status
     * 
     * @param array|string $status Status or array of statuses to count
     * @return int Number of reclamations with the given status(es)
     */
    public function countByStatus($status): int
    {
        $qb = $this->createQueryBuilder('r');
        
        if (is_array($status)) {
            $qb->where('r.status IN (:statuses)')
               ->setParameter('statuses', $status);
        } else {
            $qb->where('r.status = :status')
               ->setParameter('status', $status);
        }
        
        return $qb->select('COUNT(r.id)')
                 ->getQuery()
                 ->getSingleScalarResult();
    }

    /**
     * Get monthly statistics for reclamations
     * 
     * @return array Monthly reclamation counts
     */
    public function getMonthlyStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $currentYear = date('Y');
        
        $sql = "
            SELECT 
                MONTH(date) as month,
                COUNT(id) as count
            FROM reclamation
            WHERE YEAR(date) = :year
            GROUP BY MONTH(date)
            ORDER BY month ASC
        ";
        
        $result = $conn->executeQuery($sql, ['year' => $currentYear])->fetchAllAssociative();
        
        // Initialize all months with 0
        $monthlyData = array_fill(1, 12, 0);
        
        // Fill in actual data where available
        foreach ($result as $row) {
            $monthlyData[(int)$row['month']] = (int)$row['count'];
        }
        
        return $monthlyData;
    }
} 