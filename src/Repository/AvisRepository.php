<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 *
 * @method Avis|null find($id, $lockMode = null, $lockVersion = null)
 * @method Avis|null findOneBy(array $criteria, array $orderBy = null)
 * @method Avis[]    findAll()
 * @method Avis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Find reviews for a specific driver
     *
     * @param int $driverId The ID of the driver
     * @return Avis[] Returns an array of Avis objects
     */
    public function findByDriver(int $driverId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.conducteur = :driverId')
            ->setParameter('driverId', $driverId)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews for a specific passenger
     *
     * @param int $passengerId The ID of the passenger
     * @return Avis[] Returns an array of Avis objects
     */
    public function findByPassenger(int $passengerId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.passager = :passengerId')
            ->setParameter('passengerId', $passengerId)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find latest reviews
     *
     * @param int $limit The maximum number of reviews to return
     * @return Avis[] Returns an array of Avis objects
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Get the average rating across all reviews
     *
     * @return float The average rating
     */
    public function getAverageRating(): float
    {
        try {
            return (float) $this->createQueryBuilder('a')
                ->select('AVG(a.rating)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Count reviews with a specific rating
     *
     * @param int $rating The rating value (1-5)
     * @return int The number of reviews with the given rating
     */
    public function countByRating(int $rating): int
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.rating = :rating')
                ->setParameter('rating', $rating)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Count reviews submitted this week
     *
     * @return int The number of reviews submitted this week
     */
    public function countThisWeek(): int
    {
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week');
        $endOfWeek->setTime(23, 59, 59);
        
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.date >= :startOfWeek')
                ->andWhere('a.date <= :endOfWeek')
                ->setParameter('startOfWeek', $startOfWeek)
                ->setParameter('endOfWeek', $endOfWeek)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get the distribution of ratings across all reviews
     *
     * @return array An array containing the count for each rating (1-5)
     */
    public function getRatingDistribution(): array
    {
        $distribution = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->countByRating($i);
        }
        
        return $distribution;
    }
    
    /**
     * Get monthly statistics for avis
     * 
     * @return array Monthly avis counts
     */
    public function getMonthlyStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $currentYear = date('Y');
        
        $sql = "
            SELECT 
                MONTH(date) as month,
                COUNT(id) as count
            FROM avis
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
    
    /**
     * Find reviews by filters
     *
     * @param array $filters Filters for reviews
     * @return Avis[] Returns an array of Avis objects
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('a');
        
        // Filter by rating
        if (isset($filters['note']) && $filters['note']) {
            $qb->andWhere('a.rating = :note')
               ->setParameter('note', $filters['note']);
        }
        
        // Filter by date
        if (isset($filters['date']) && $filters['date']) {
            try {
                // Handle special cases for date filtering
                switch ($filters['date']) {
                    case 'today':
                        $today = new \DateTime('today');
                        $tomorrow = new \DateTime('tomorrow');
                        
                        $qb->andWhere('a.date >= :start')
                           ->andWhere('a.date < :end')
                           ->setParameter('start', $today)
                           ->setParameter('end', $tomorrow);
                        break;
                        
                    case 'week':
                        $startOfWeek = new \DateTime('monday this week');
                        $endOfWeek = new \DateTime('sunday this week');
                        $endOfWeek->setTime(23, 59, 59);
                        
                        $qb->andWhere('a.date >= :startOfWeek')
                           ->andWhere('a.date <= :endOfWeek')
                           ->setParameter('startOfWeek', $startOfWeek)
                           ->setParameter('endOfWeek', $endOfWeek);
                        break;
                        
                    case 'month':
                        $startOfMonth = new \DateTime('first day of this month');
                        $endOfMonth = new \DateTime('last day of this month');
                        $endOfMonth->setTime(23, 59, 59);
                        
                        $qb->andWhere('a.date >= :startOfMonth')
                           ->andWhere('a.date <= :endOfMonth')
                           ->setParameter('startOfMonth', $startOfMonth)
                           ->setParameter('endOfMonth', $endOfMonth);
                        break;
                        
                    case 'year':
                        $startOfYear = new \DateTime('first day of January ' . date('Y'));
                        $endOfYear = new \DateTime('last day of December ' . date('Y'));
                        $endOfYear->setTime(23, 59, 59);
                        
                        $qb->andWhere('a.date >= :startOfYear')
                           ->andWhere('a.date <= :endOfYear')
                           ->setParameter('startOfYear', $startOfYear)
                           ->setParameter('endOfYear', $endOfYear);
                        break;
                        
                    default:
                        // Try to parse as a specific date
                        $date = new \DateTime($filters['date']);
                        $tomorrow = clone $date;
                        $tomorrow->modify('+1 day');
                        
                        $qb->andWhere('a.date >= :date')
                           ->andWhere('a.date < :tomorrow')
                           ->setParameter('date', $date)
                           ->setParameter('tomorrow', $tomorrow);
                        break;
                }
            } catch (\Exception $e) {
                // Log error but continue with query
                error_log('Error parsing date filter: ' . $e->getMessage());
            }
        }
        
        // Get results ordered by date
        $results = $qb->orderBy('a.date', 'DESC')
                     ->getQuery()
                     ->getResult();
        
        // Filter by sentiment if specified
        if (isset($filters['sentiment']) && $filters['sentiment']) {
            return array_filter($results, function($avis) use ($filters) {
                $commentaire = $avis->getCommentaire();
                return $this->analyzeSentiment($commentaire) === $filters['sentiment'];
            });
        }
        
        return $results;
    }
    
    /**
     * Count reviews by sentiment
     *
     * @param string $sentiment The sentiment to count ('positive', 'neutral', 'negative')
     * @return int The number of reviews with the given sentiment
     */
    public function countBySentiment(string $sentiment): int
    {
        $allAvis = $this->findAll();
        $count = 0;
        
        foreach ($allAvis as $avis) {
            if ($this->analyzeSentiment($avis->getCommentaire()) === $sentiment) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Simple sentiment analysis based on keywords
     */
    private function analyzeSentiment(?string $comment): string
    {
        if (!$comment) {
            return 'neutral';
        }
        
        // Convert to lowercase for easier matching
        $text = strtolower($comment);
        
        // Define keyword lists
        $positiveWords = ['bien', 'super', 'excellent', 'génial', 'parfait', 'satisfait', 
                        'merci', 'agréable', 'recommande', 'ponctuel', 'sympathique', 'souriant', 
                        'propre', 'confortable', 'fiable', 'sécurité', 'professionnel', 'impeccable',
                        'plaisir', 'gentil', 'accueillant', 'bravo'];
                        
        $negativeWords = ['mauvais', 'horrible', 'déçu', 'déception', 'décevant', 'problème', 
                         'retard', 'sale', 'dangereux', 'dommage', 'malheureusement', 'désagréable',
                         'déplorable', 'insatisfait', 'mécontentement', 'accident', 'désastre', 'bruit',
                         'odeur', 'inconfortable', 'impoli', 'dérangé', 'ennuyeux', 'pénible',
                         'irrespectueux', 'inadmissible', 'inacceptable', 'négatif', 'nulle', 'médiocre',
                         'terrible', 'pas bon', 'pas content'];
        
        // Négations qui inversent le sens
        $negations = ['pas', 'jamais', 'aucun', 'rien', 'sans', 'aucune'];
        
        // Count matches with improved weightings
        $positiveCount = 0;
        $negativeCount = 0;
        
        // Évaluer d'abord la négation avec mots positifs qui devient négatif
        foreach ($positiveWords as $word) {
            $count = substr_count($text, $word);
            // Vérifier si le mot positif est précédé d'une négation
            foreach ($negations as $negation) {
                if (strpos($text, "$negation $word") !== false || strpos($text, "$negation de $word") !== false) {
                    $negativeCount += 1.5; // Négation d'un terme positif a plus de poids négatif
                    $count--;  // Ne pas compter comme positif
                }
            }
            $positiveCount += $count;
        }
        
        // Comptage des mots négatifs (en évitant double comptage avec négations)
        foreach ($negativeWords as $word) {
            $count = substr_count($text, $word);
            
            // Vérifier si le mot négatif est précédé d'une négation (qui le rend positif)
            foreach ($negations as $negation) {
                if (strpos($text, "$negation $word") !== false || strpos($text, "$negation de $word") !== false) {
                    $positiveCount += 1; // Négation d'un terme négatif est légèrement positif
                    $count--;  // Ne pas compter comme négatif
                }
            }
            
            // Donner plus de poids aux mots fortement négatifs
            if (in_array($word, ['horrible', 'dangereux', 'inadmissible', 'inacceptable', 'terrible'])) {
                $count *= 1.5;
            }
            
            $negativeCount += $count;
        }
        
        // Si le texte est court et contient un mot négatif fort, considérer comme négatif
        $shortText = strlen($text) < 50;
        $hasStrongNegative = strpos($text, 'horrible') !== false || 
                             strpos($text, 'dangereux') !== false || 
                             strpos($text, 'inacceptable') !== false;
        
        if ($shortText && $hasStrongNegative && $negativeCount > 0) {
            return 'negative';
        }
        
        // Threshold adjustment - be more sensitive to negative sentiment
        if ($negativeCount > $positiveCount * 0.8) {
            return 'negative';
        } elseif ($positiveCount > $negativeCount * 1.2) {
            return 'positive';
        } else {
            return 'neutral';
        }
    }
} 