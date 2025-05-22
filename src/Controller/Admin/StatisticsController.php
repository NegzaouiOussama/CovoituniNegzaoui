<?php

namespace App\Controller\Admin;

use App\Repository\AvisRepository;
use App\Repository\ReclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin/statistics')]
class StatisticsController extends AbstractController
{
    #[Route('/', name: 'app_admin_statistics')]
    public function index(ReclamationRepository $reclamationRepository, AvisRepository $avisRepository, EntityManagerInterface $entityManager): Response
    {
        // Debug array to store query results
        $debug = [];
        
        try {
            // Réclamations par statut
            $reclamationStats = $reclamationRepository->createQueryBuilder('r')
                ->select('r.status as status, COUNT(r.id) as count')
                ->groupBy('r.status')
                ->getQuery()
                ->getResult();
                
            $debug['reclamationStats'] = $reclamationStats;

            // Format data for pie chart
            $reclamationPieData = [['Status', 'Count']];
            
            // Check if we have data, otherwise provide sample data
            if (empty($reclamationStats)) {
                // Sample data for demonstration
                $reclamationPieData[] = ['En attente', 5];
                $reclamationPieData[] = ['En cours', 3];
                $reclamationPieData[] = ['Résolu', 8];
                $reclamationPieData[] = ['Rejeté', 2];
                $debug['usingSamplePieData'] = true;
            } else {
                foreach ($reclamationStats as $stat) {
                    $statusLabel = 'N/A';
                    if (!empty($stat['status'])) {
                        switch($stat['status']) {
                            case 'pending':
                                $statusLabel = 'En attente';
                                break;
                            case 'in_progress':
                                $statusLabel = 'En cours';
                                break;
                            case 'resolved':
                                $statusLabel = 'Résolu';
                                break;
                            case 'rejected':
                                $statusLabel = 'Rejeté';
                                break;
                            default:
                                $statusLabel = ucfirst($stat['status']);
                        }
                    }
                    $reclamationPieData[] = [$statusLabel, (int)$stat['count']];
                }
                $debug['usingSamplePieData'] = false;
            }
            
            $debug['finalPieData'] = $reclamationPieData;
            
            // Set options for pie chart
            $pieChartOptions = [
                'title' => 'Répartition des Réclamations par Statut',
                'height' => 400,
                'width' => 600,
                'titleTextStyle' => [
                    'bold' => true,
                    'color' => '#07600',
                    'italic' => true,
                    'fontName' => 'Arial',
                    'fontSize' => 20
                ]
            ];
            
            // Réclamations par mois
            $connection = $entityManager->getConnection();
            
            try {
                $monthlyReclamationsQuery = "
                    SELECT YEAR(date) as year, MONTH(date) as month, COUNT(id) as count 
                    FROM reclamation 
                    GROUP BY YEAR(date), MONTH(date) 
                    ORDER BY year, month
                ";
                
                $debug['monthlyReclamationsQuery'] = $monthlyReclamationsQuery;
                $monthlyReclamations = $connection->executeQuery($monthlyReclamationsQuery)->fetchAllAssociative();
                $debug['monthlyReclamations'] = $monthlyReclamations;
            } catch (\Exception $e) {
                $debug['monthlyReclamationsError'] = $e->getMessage();
                $monthlyReclamations = [];
            }

            // Format data for line chart
            $monthlyData = [['Mois', 'Nombre de Réclamations']];
            
            // Check if we have data, otherwise provide sample data
            if (empty($monthlyReclamations)) {
                // Sample data for demonstration - past 6 months
                $currentMonth = (int)date('m');
                $currentYear = (int)date('Y');
                
                for ($i = 5; $i >= 0; $i--) {
                    $month = $currentMonth - $i;
                    $year = $currentYear;
                    
                    if ($month <= 0) {
                        $month += 12;
                        $year -= 1;
                    }
                    
                    $date = sprintf('%d-%02d', $year, $month);
                    $monthlyData[] = [$date, rand(1, 10)]; // Random count between 1-10
                }
                $debug['usingMonthlyReclamationsSample'] = true;
            } else {
                foreach ($monthlyReclamations as $stat) {
                    if (isset($stat['year']) && isset($stat['month'])) {
                        $date = sprintf('%d-%02d', $stat['year'], $stat['month']);
                        $monthlyData[] = [$date, (int)$stat['count']];
                    }
                }
                $debug['usingMonthlyReclamationsSample'] = false;
            }
            
            $debug['finalMonthlyData'] = $monthlyData;
            
            // Set options for line chart
            $lineChartOptions = [
                'title' => 'Évolution des Réclamations par Mois',
                'height' => 400,
                'width' => 900,
                'curveType' => 'function',
                'lineWidth' => 3,
                'legend' => ['position' => 'bottom']
            ];
            
            // Moyenne des avis par mois
            try {
                $monthlyAvisQuery = "
                    SELECT YEAR(date) as year, MONTH(date) as month, AVG(rating) as average 
                    FROM avis 
                    WHERE rating IS NOT NULL
                    GROUP BY YEAR(date), MONTH(date) 
                    ORDER BY year, month
                ";
                
                $debug['monthlyAvisQuery'] = $monthlyAvisQuery;
                $monthlyAvis = $connection->executeQuery($monthlyAvisQuery)->fetchAllAssociative();
                $debug['monthlyAvis'] = $monthlyAvis;
            } catch (\Exception $e) {
                $debug['monthlyAvisError'] = $e->getMessage();
                $monthlyAvis = [];
            }

            // Format data for column chart
            $avisData = [['Mois', 'Note Moyenne']];
            
            // Check if we have data, otherwise provide sample data
            if (empty($monthlyAvis)) {
                // Sample data for demonstration - past 6 months
                $currentMonth = (int)date('m');
                $currentYear = (int)date('Y');
                
                for ($i = 5; $i >= 0; $i--) {
                    $month = $currentMonth - $i;
                    $year = $currentYear;
                    
                    if ($month <= 0) {
                        $month += 12;
                        $year -= 1;
                    }
                    
                    $date = sprintf('%d-%02d', $year, $month);
                    $avisData[] = [$date, round(rand(30, 50) / 10, 1)]; // Random average between 3.0-5.0
                }
                $debug['usingMonthlySample'] = true;
            } else {
                foreach ($monthlyAvis as $stat) {
                    if (isset($stat['year']) && isset($stat['month'])) {
                        $date = sprintf('%d-%02d', $stat['year'], $stat['month']);
                        $avisData[] = [$date, round((float)$stat['average'], 2)];
                    }
                }
                $debug['usingMonthlySample'] = false;
            }
            
            $debug['finalAvisData'] = $avisData;
            
            // Set options for column chart
            $columnChartOptions = [
                'title' => 'Moyenne des Avis par Mois',
                'height' => 400,
                'width' => 900,
                'legend' => ['position' => 'none'],
                'vAxis' => ['title' => 'Note Moyenne'],
                'hAxis' => ['title' => 'Mois']
            ];

            return $this->render('admin/statistics/index.html.twig', [
                'reclamationPieData' => $reclamationPieData,
                'pieChartOptions' => $pieChartOptions,
                'monthlyData' => $monthlyData,
                'lineChartOptions' => $lineChartOptions,
                'avisData' => $avisData,
                'columnChartOptions' => $columnChartOptions,
                'hasDemoData' => empty($reclamationStats) && empty($monthlyReclamations) && empty($monthlyAvis),
                'debug' => $debug
            ]);
        } catch (\Exception $e) {
            // Log the error and render an error page
            return $this->render('admin/statistics/index.html.twig', [
                'error' => $e->getMessage(),
                'debug' => $debug
            ]);
        }
    }
} 