<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\AnnonceRepository;
use App\Repository\CategorieRepository;
use App\Repository\ReclamationRepository;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        ReclamationRepository $reclamationRepository,
        UtilisateurRepository $utilisateurRepository,
        AnnonceRepository $annonceRepository,
        ReservationRepository $reservationRepository,
        AvisRepository $avisRepository,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->getUser();
        
        // Get count of active reclamations (pending and in_progress)
        $reclamationsCount = $reclamationRepository->countByStatus(['pending', 'in_progress']);
        
        // Get all categories
        $categories = $categorieRepository->findAll();
        $categoriesWithCars = $categorieRepository->findWithCars();
        $recentCategories = $categorieRepository->findRecent(5);
        
        // Get monthly statistics
        $reclamationsMonthlyData = $reclamationRepository->getMonthlyStats();
        $avisMonthlyData = $avisRepository->getMonthlyStats();
        
        // Format monthly data as key-value pairs for the template
        $formattedReclamationsMonthlyData = [];
        $formattedAvisMonthlyData = [];
        
        foreach ($reclamationsMonthlyData as $month => $count) {
            $formattedReclamationsMonthlyData[$month] = $count;
        }
        
        foreach ($avisMonthlyData as $month => $count) {
            $formattedAvisMonthlyData[$month] = $count;
        }
        
        // Comprehensive statistics for the dashboard
        $stats = [
            // User statistics
            'users' => [
                'total' => $utilisateurRepository->count([]),
                'conducteurs' => $utilisateurRepository->countByRole('CONDUCTEUR'),
                'passagers' => $utilisateurRepository->countByRole('PASSAGER'),
                'active' => $utilisateurRepository->countActive(),
            ],
            
            // Category statistics
            'categories' => [
                'total' => count($categories),
                'with_cars' => count($categoriesWithCars),
                'recent' => $recentCategories,
            ],
            
            // Annonce statistics
            'annonces' => [
                'total' => $annonceRepository->count([]),
                'today' => $annonceRepository->countCreatedToday(),
                'pending' => $annonceRepository->countByStatus('pending'),
                'confirmed' => $annonceRepository->countByStatus('confirmed'),
            ],
            
            // Reservation statistics
            'reservations' => [
                'total' => $reservationRepository->count([]),
                'confirmed' => $reservationRepository->countByStatus('confirmed'),
                'pending' => $reservationRepository->countByStatus('pending'),
                'canceled' => $reservationRepository->countByStatus('canceled'),
            ],
            
            // Avis statistics
            'avis' => [
                'total' => $avisRepository->count([]),
                'avg_rating' => $avisRepository->getAverageRating(),
                'this_week' => $avisRepository->countThisWeek(),
                'distribution' => [
                    $avisRepository->countByRating(1),
                    $avisRepository->countByRating(2),
                    $avisRepository->countByRating(3),
                    $avisRepository->countByRating(4),
                    $avisRepository->countByRating(5),
                ],
                'monthly' => $formattedAvisMonthlyData,
            ],
            
            // Reclamation statistics
            'reclamations' => [
                'active' => $reclamationsCount,
                'pending' => $reclamationRepository->countByStatus('pending'),
                'in_progress' => $reclamationRepository->countByStatus('in_progress'),
                'resolved' => $reclamationRepository->countByStatus('resolved'),
                'rejected' => $reclamationRepository->countByStatus('rejected'),
                'total' => $reclamationRepository->countByStatus('pending') + 
                          $reclamationRepository->countByStatus('in_progress') + 
                          $reclamationRepository->countByStatus('resolved') + 
                          $reclamationRepository->countByStatus('rejected'),
                'monthly' => $formattedReclamationsMonthlyData,
            ],
            
            // Recent users
            'recent_users' => $utilisateurRepository->findRecent(5),
            
            // Recent rides
            'recent_rides' => $annonceRepository->findRecent(5),
            
            // Chart data - User growth over last 12 weeks
            'user_growth' => $this->getUserGrowthData($entityManager),
            
            // Chart data - Ride and booking activity over last 12 days
            'ride_activity' => $this->getRideActivityData($entityManager),
            
            // System alerts
            'alerts' => $this->getSystemAlerts($entityManager),
        ];
        
        return $this->render('admin/dashboard.html.twig', [
            'user' => $user,
            'reclamations_count' => $reclamationsCount,
            'stats' => $stats
        ]);
    }
    
    /**
     * Generate user growth data for the chart
     */
    private function getUserGrowthData(EntityManagerInterface $entityManager): array
    {
        $conn = $entityManager->getConnection();
        
        // Get user registration data for the last 12 weeks
        $query = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%u') AS week,
                COUNT(id) AS count
            FROM utilisateur
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY week
            ORDER BY week ASC
        ";
        
        $result = $conn->executeQuery($query)->fetchAllAssociative();
        
        $labels = [];
        $data = [];
        
        // Format the data for Chart.js
        foreach ($result as $row) {
            $weekParts = explode('-', $row['week']);
            $year = $weekParts[0];
            $week = $weekParts[1];
            
            // Format the week label
            $labels[] = "Semaine {$week}, {$year}";
            $data[] = (int)$row['count'];
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
    
    /**
     * Generate ride and booking activity data for the chart
     */
    private function getRideActivityData(EntityManagerInterface $entityManager): array
    {
        $conn = $entityManager->getConnection();
        
        // Get ride and booking data for the last 12 days
        $rideQuery = "
            SELECT 
                DATE(date_publication) AS day,
                COUNT(id) AS count
            FROM annonce
            WHERE date_publication >= DATE_SUB(NOW(), INTERVAL 12 DAY)
            GROUP BY day
            ORDER BY day ASC
        ";
        
        $bookingQuery = "
            SELECT 
                DATE(date_reservation) AS day,
                COUNT(id) AS count
            FROM reservation
            WHERE date_reservation >= DATE_SUB(NOW(), INTERVAL 12 DAY)
            GROUP BY day
            ORDER BY day ASC
        ";
        
        $rideResult = $conn->executeQuery($rideQuery)->fetchAllAssociative();
        $bookingResult = $conn->executeQuery($bookingQuery)->fetchAllAssociative();
        
        // Generate dates for the last 12 days
        $dates = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} day");
            $dates[] = $date->format('Y-m-d');
        }
        
        // Format the data for Chart.js
        $labels = [];
        $rides = array_fill(0, 12, 0);
        $bookings = array_fill(0, 12, 0);
        
        foreach ($dates as $index => $date) {
            $labels[] = (new \DateTime($date))->format('d/m');
            
            // Find matching ride data
            foreach ($rideResult as $row) {
                if ($row['day'] === $date) {
                    $rides[$index] = (int)$row['count'];
                    break;
                }
            }
            
            // Find matching booking data
            foreach ($bookingResult as $row) {
                if ($row['day'] === $date) {
                    $bookings[$index] = (int)$row['count'];
                    break;
                }
            }
        }
        
        return [
            'labels' => $labels,
            'rides' => $rides,
            'bookings' => $bookings
        ];
    }
    
    /**
     * Generate system alerts for the dashboard
     */
    private function getSystemAlerts(EntityManagerInterface $entityManager): array
    {
        $alerts = [];
        
        // Get pending reclamations count
        $conn = $entityManager->getConnection();
        $pendingReclamations = $conn->executeQuery(
            "SELECT COUNT(id) as count FROM reclamation WHERE status = 'pending'"
        )->fetchAssociative();
        
        if ($pendingReclamations['count'] > 0) {
            $alerts[] = [
                'title' => 'Réclamations en attente',
                'message' => 'Il y a ' . $pendingReclamations['count'] . ' réclamation(s) en attente de traitement.',
                'color' => 'orange',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
            ];
        }
        
        // Alert about recent users instead of unverified users
        $recentUsers = $conn->executeQuery(
            "SELECT COUNT(id) as count FROM utilisateur WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetchAssociative();
        
        if ($recentUsers['count'] > 0) {
            $alerts[] = [
                'title' => 'Nouveaux utilisateurs',
                'message' => 'Il y a ' . $recentUsers['count'] . ' nouveau(x) utilisateur(s) inscrit(s) cette semaine.',
                'color' => 'blue',
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
            ];
        }
        
        return $alerts;
    }

    #[Route('/ckeditor-demo', name: 'app_admin_ckeditor_demo')]
    public function ckeditorDemo(): Response
    {
        return $this->render('admin/ckeditor_demo.html.twig', [
            'page_title' => 'Démo CKEditor',
        ]);
    }
} 