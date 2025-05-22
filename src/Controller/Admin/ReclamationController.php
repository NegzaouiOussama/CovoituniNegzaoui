<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReclamationResponseType;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Ne pas mettre de name ici pour éviter les conflits
#[Route('/admin/reclamation')]
class ReclamationController extends AbstractController
{
    private $pdf;
    
    public function __construct(Pdf $pdf)
    {
        $this->pdf = $pdf;
    }
    
    #[Route('/', name: 'app_admin_reclamation_index', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository, PaginatorInterface $paginator): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $filter = $request->query->get('filter');
        $search = $request->query->get('search');
        
        // Create query based on filter
        $queryBuilder = $reclamationRepository->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC');
            
        // Filter reclamations based on the filter parameter
        if ($filter && in_array($filter, ['pending', 'in_progress', 'resolved', 'rejected'])) {
            $queryBuilder->andWhere('r.status = :status')
                ->setParameter('status', $filter);
        }
        
        // Filter by search term if provided
        if ($search) {
            $queryBuilder->andWhere('r.subject LIKE :search OR r.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Paginate the results
        $pagination = $paginator->paginate(
            $queryBuilder, // Query
            $request->query->getInt('page', 1), // Page number
            5 // Items per page
        );
        
        return $this->render('admin/reclamation/index.html.twig', [
            'pagination' => $pagination,
            'user' => $this->getUser(),
        ]);
    }
    
    #[Route('/{id}', name: 'app_admin_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'user' => $this->getUser(),
        ]);
    }
    
    #[Route('/{id}/reply', name: 'app_admin_reclamation_reply', methods: ['GET', 'POST'])]
    public function reply(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Create a new Reponse object
        $reponse = new Reponse();
        $reponse->setReclamation($reclamation);
        $reponse->setAdminUsername($this->getUser()->getUserIdentifier());
        
        $form = $this->createForm(ReponseType::class, $reponse);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Update the reclamation status if needed
            $newStatus = $request->request->get('status');
            if ($newStatus && in_array($newStatus, ['pending', 'in_progress', 'resolved', 'rejected'])) {
                $reclamation->setStatus($newStatus);
            }
            
            // Save the response
            $entityManager->persist($reponse);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre réponse a été enregistrée avec succès');
            return $this->redirectToRoute('app_admin_reclamation_show', ['id' => $reclamation->getId()]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire');
        }
        
        return $this->render('admin/reclamation/reply.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            
            $this->addFlash('success', 'La réclamation a été supprimée avec succès');
        }
        
        return $this->redirectToRoute('app_admin_reclamation_index');
    }
    
    #[Route('/{id}/change-status', name: 'app_admin_reclamation_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $newStatus = $request->request->get('status');
        if (!in_array($newStatus, ['pending', 'in_progress', 'resolved', 'rejected'])) {
            $this->addFlash('error', 'Statut invalide');
            return $this->redirectToRoute('app_admin_reclamation_show', ['id' => $reclamation->getId()]);
        }
        
        $reclamation->setStatus($newStatus);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le statut de la réclamation a été modifié avec succès');
        return $this->redirectToRoute('app_admin_reclamation_show', ['id' => $reclamation->getId()]);
    }

    /**
     * Action pour répondre à une réclamation
     */
    #[Route('/{id}/respond', name: 'app_admin_reclamation_respond', methods: ['GET', 'POST'])]
    public function respond(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(ReclamationResponseType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update reclamation status
            $entityManager->persist($reclamation);
            
            // Create a new response if content is provided
            $content = $form->get('content')->getData();
            if (!empty($content)) {
                // Create and configure the response
                $reponse = new Reponse();
                $reponse->setContent($content);
                $reponse->setReclamation($reclamation);
                $reponse->setAdminUsername($this->getUser()->getUserIdentifier());
                
                // Save the response
                $entityManager->persist($reponse);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'La réponse a été enregistrée avec succès.');
            
            // Redirection vers la page de détail
            return $this->redirectToRoute('app_admin_reclamation_show', ['id' => $reclamation->getId()]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire');
        }

        return $this->render('admin/reclamation/respond.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Action pour mettre à jour tous les statuts
     */
    #[Route('/update-all-status', name: 'app_admin_reclamation_update_all_status', methods: ['GET'])]
    public function updateAllStatus(ReclamationRepository $reclamationRepository, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupérer toutes les réclamations
        $reclamations = $reclamationRepository->findAll();
        $updatedCount = 0;
        
        // Mise à jour des statuts selon une logique définie
        foreach ($reclamations as $reclamation) {
            // Vérifier si le statut est vide ou null
            if (empty($reclamation->getStatus())) {
                // Définir un statut par défaut si nécessaire
                $reclamation->setStatus('pending');
                $updatedCount++;
            }
            
            // Vérifier si le statut est dans un format ancien et le convertir
            // Exemple: convertir "en_attente" en "pending"
            if ($reclamation->getStatus() === 'en_attente') {
                $reclamation->setStatus('pending');
                $updatedCount++;
            } else if ($reclamation->getStatus() === 'en_cours') {
                $reclamation->setStatus('in_progress');
                $updatedCount++;
            } else if ($reclamation->getStatus() === 'resolu') {
                $reclamation->setStatus('resolved');
                $updatedCount++;
            } else if ($reclamation->getStatus() === 'rejete') {
                $reclamation->setStatus('rejected');
                $updatedCount++;
            }
            
            // Vous pouvez ajouter d'autres règles de conversion ici
        }
        
        // Enregistrer toutes les modifications
        $entityManager->flush();
        
        $this->addFlash('success', $updatedCount . ' réclamations ont été mises à jour avec succès.');
        return $this->redirectToRoute('app_admin_reclamation_index');
    }

    /**
     * Action pour corriger les problèmes de statut dans une seule réclamation
     */
    #[Route('/{id}/fix-status', name: 'app_admin_reclamation_fix_status', methods: ['GET'])]
    public function fixStatus(Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Si le statut est vide, le définir à "pending"
        if (empty($reclamation->getStatus())) {
            $reclamation->setStatus('pending');
        }
        
        // S'assurer que le statut est dans un format valide
        $validStatuses = ['pending', 'in_progress', 'resolved', 'rejected'];
        if (!in_array($reclamation->getStatus(), $validStatuses)) {
            // Convertir l'ancien format si possible
            switch (strtolower($reclamation->getStatus())) {
                case 'en attente':
                case 'en_attente':
                    $reclamation->setStatus('pending');
                    break;
                case 'en cours':
                case 'en_cours':
                    $reclamation->setStatus('in_progress');
                    break;
                case 'résolu':
                case 'resolu':
                    $reclamation->setStatus('resolved');
                    break;
                case 'rejeté':
                case 'rejete':
                    $reclamation->setStatus('rejected');
                    break;
                default:
                    // Par défaut, mettre en attente
                    $reclamation->setStatus('pending');
            }
        }
        
        // Enregistrer les modifications
        $entityManager->flush();
        
        $this->addFlash('success', 'Le statut de la réclamation a été corrigé avec succès.');
        return $this->redirectToRoute('app_admin_reclamation_show', ['id' => $reclamation->getId()]);
    }

    #[Route('/{id}/pdf', name: 'app_admin_reclamation_pdf', methods: ['GET'])]
    public function generatePdf(Reclamation $reclamation, ReclamationRepository $reclamationRepository): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all reclamations
        $reclamations = $reclamationRepository->findAll();
        
        // Create CSV content
        $csvContent = "ID;Sujet;Description;Date;Statut;Utilisateur;Email\n";
        
        foreach ($reclamations as $reclamation) {
            $csvContent .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s\n",
                $reclamation->getId(),
                str_replace(';', ',', $reclamation->getSubject()),
                str_replace(';', ',', substr($reclamation->getDescription(), 0, 100)) . '...',
                $reclamation->getDate()->format('d/m/Y H:i'),
                $reclamation->getStatus(),
                $reclamation->getUser() ? $reclamation->getUser()->getNom() . ' ' . $reclamation->getUser()->getPrenom() : 'N/A',
                $reclamation->getUser() ? $reclamation->getUser()->getEmail() : 'N/A'
            );
        }
        
        // Create response with CSV content
        $response = new Response($csvContent);
        
        // Set headers
        $filename = 'reclamations_export_' . date('Y-m-d') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Export reclamations to Excel format
     */
    #[Route('/export/excel', name: 'app_admin_reclamation_export_excel', methods: ['GET'])]
    public function exportExcel(ReclamationRepository $reclamationRepository): Response
    {
        // Make sure only users with ROLE_ADMIN can access this page
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all reclamations
        $reclamations = $reclamationRepository->findAll();
        
        // Create a spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Sujet');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Date');
        $sheet->setCellValue('E1', 'Statut');
        $sheet->setCellValue('F1', 'Utilisateur');
        $sheet->setCellValue('G1', 'Email');
        
        // Style the header row
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DDDDDD');
            
        // Add data
        $row = 2;
        foreach ($reclamations as $reclamation) {
            $sheet->setCellValue('A' . $row, $reclamation->getId());
            $sheet->setCellValue('B' . $row, $reclamation->getSubject());
            $sheet->setCellValue('C' . $row, substr($reclamation->getDescription(), 0, 100) . '...');
            $sheet->setCellValue('D' . $row, $reclamation->getDate()->format('d/m/Y H:i'));
            $sheet->setCellValue('E' . $row, $reclamation->getStatus());
            $sheet->setCellValue('F' . $row, $reclamation->getUser() ? $reclamation->getUser()->getNom() . ' ' . $reclamation->getUser()->getPrenom() : 'N/A');
            $sheet->setCellValue('G' . $row, $reclamation->getUser() ? $reclamation->getUser()->getEmail() : 'N/A');
            $row++;
        }
        
        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'reclamations_export_' . date('Y-m-d') . '.xlsx';
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'reclamations');
        $writer->save($tempFile);
        
        // Return the file as a response
        return $this->file($tempFile, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
} 