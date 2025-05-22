<?php

namespace App\Controller\Admin;

use App\Entity\Avis;
use App\Entity\ReponseAvis;
use App\Form\ReponseAvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

#[Route('/admin/avis', name: 'app_admin_avis_')]
class AvisController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, AvisRepository $avisRepository): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get filter parameters
        $filter = [
            'note' => $request->query->get('note'),
            'date' => $request->query->get('date'),
            'sentiment' => $request->query->get('sentiment')
        ];
        
        // Get all reviews based on filters
        $avis = $avisRepository->findByFilters($filter);
        
        // Get statistics
        $stats = [
            'averageRating' => $avisRepository->getAverageRating(),
            'distribution' => $avisRepository->getRatingDistribution(),
            'total' => $avisRepository->count([]),
            'recent' => $avisRepository->findLatest(5),
            'sentiment' => [
                'positive' => $avisRepository->countBySentiment('positive'),
                'neutral' => $avisRepository->countBySentiment('neutral'),
                'negative' => $avisRepository->countBySentiment('negative'),
            ]
        ];
        
        return $this->render('admin/avis/index.html.twig', [
            'avis' => $avis,
            'stats' => $stats,
            'filter' => $filter,
            'user' => $this->getUser()
        ]);
    }
    
    #[Route('/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function show(Request $request, Avis $avis, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Create a new response to the review
        $reponse = new ReponseAvis();
        $reponse->setAvis($avis);
        $reponse->setAdmin($this->getUser());
        
        $form = $this->createForm(ReponseAvisType::class, $reponse);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $reponse->setDate(new \DateTime());
            $entityManager->persist($reponse);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre réponse a été ajoutée avec succès.');
            return $this->redirectToRoute('app_admin_avis_show', ['id' => $avis->getId()]);
        }
        
        return $this->render('admin/avis/show.html.twig', [
            'avis' => $avis,
            'form' => $form->createView(),
            'user' => $this->getUser()
        ]);
    }
    
    #[Route('/export/csv', name: 'export_csv', methods: ['GET'])]
    public function exportCsv(AvisRepository $avisRepository): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all reviews
        $avis = $avisRepository->findAll();
        
        // Create CSV content
        $csvContent = "ID,Date,Passager,Conducteur,Note,Commentaire,Sentiment\n";
        
        foreach ($avis as $review) {
            $csvContent .= $review->getId() . ',';
            $csvContent .= ($review->getDate() ? $review->getDate()->format('Y-m-d') : 'N/A') . ',';
            $csvContent .= ($review->getPassager() ? $review->getPassager()->getEmail() : 'N/A') . ',';
            $csvContent .= ($review->getConducteur() ? $review->getConducteur()->getEmail() : 'N/A') . ',';
            $csvContent .= $review->getRating() . ',';
            // Escape quotes in commentary for CSV format
            $comment = str_replace('"', '""', $review->getCommentaire() ?: '');
            $csvContent .= '"' . $comment . '",';
            $csvContent .= $this->analyzeSentiment($review->getCommentaire()) . "\n";
        }
        
        // Create response
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="avis_export_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
    
    #[Route('/export/excel', name: 'export_excel', methods: ['GET'])]
    public function exportExcel(AvisRepository $avisRepository): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all reviews
        $avis = $avisRepository->findAll();
        
        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Passager');
        $sheet->setCellValue('D1', 'Conducteur');
        $sheet->setCellValue('E1', 'Note');
        $sheet->setCellValue('F1', 'Commentaire');
        $sheet->setCellValue('G1', 'Sentiment');
        
        // Populate data
        $row = 2;
        foreach ($avis as $review) {
            $sheet->setCellValue('A' . $row, $review->getId());
            $sheet->setCellValue('B' . $row, $review->getDate() ? $review->getDate()->format('Y-m-d') : 'N/A');
            $sheet->setCellValue('C' . $row, $review->getPassager() ? $review->getPassager()->getEmail() : 'N/A');
            $sheet->setCellValue('D' . $row, $review->getConducteur() ? $review->getConducteur()->getEmail() : 'N/A');
            $sheet->setCellValue('E' . $row, $review->getRating());
            $sheet->setCellValue('F' . $row, $review->getCommentaire());
            $sheet->setCellValue('G' . $row, $this->analyzeSentiment($review->getCommentaire()));
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create the writer
        $writer = new Xlsx($spreadsheet);
        
        // Create a temporary file
        $fileName = 'avis_export_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        // Return the file
        return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
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
        $positiveWords = ['bien', 'super', 'excellent', 'génial', 'parfait', 'satisfait', 'merci', 'agréable', 'recommande', 'ponctuel', 'sympathique', 'souriant', 'propre'];
        $negativeWords = ['mauvais', 'horrible', 'déçu', 'déception', 'décevant', 'problème', 'retard', 'pas', 'jamais', 'sale', 'dangereux', 'dommage', 'malheureusement'];
        
        // Count matches
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        // Determine sentiment based on counts
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    #[Route('/analyze-sentiment', name: 'analyze_sentiment', methods: ['POST'])]
    public function analyzeComment(Request $request): JsonResponse
    {
        // Get comment text from request
        $comment = $request->request->get('comment');
        $sentiment = $this->analyzeSentiment($comment);
        
        return new JsonResponse([
            'sentiment' => $sentiment,
            'emoji' => $this->getSentimentEmoji($sentiment)
        ]);
    }
    
    private function getSentimentEmoji(string $sentiment): string
    {
        switch ($sentiment) {
            case 'positive':
                return '😊';
            case 'negative':
                return '😞';
            default:
                return '��';
        }
    }
    
    #[Route('/export/json', name: 'export_json', methods: ['GET'])]
    public function exportJson(AvisRepository $avisRepository): JsonResponse
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all reviews
        $avis = $avisRepository->findAll();
        
        // Prepare data array for JSON
        $jsonData = [];
        
        foreach ($avis as $review) {
            $jsonData[] = [
                'id' => $review->getId(),
                'date' => $review->getDate() ? $review->getDate()->format('Y-m-d') : null,
                'passager' => $review->getPassager() ? [
                    'id' => $review->getPassager()->getId(),
                    'email' => $review->getPassager()->getEmail()
                ] : null,
                'conducteur' => $review->getConducteur() ? [
                    'id' => $review->getConducteur()->getId(),
                    'email' => $review->getConducteur()->getEmail()
                ] : null,
                'rating' => $review->getRating(),
                'commentaire' => $review->getCommentaire(),
                'sentiment' => $this->analyzeSentiment($review->getCommentaire())
            ];
        }
        
        // Create response with proper filename and headers
        $response = new JsonResponse($jsonData);
        $response->headers->set('Content-Disposition', 'attachment; filename="avis_export_' . date('Y-m-d') . '.json"');
        
        return $response;
    }
} 