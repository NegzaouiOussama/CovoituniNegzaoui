<?php

namespace App\Controller\Admin;

use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use App\Repository\TrajetRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/annonces')]
class AnnonceController extends AbstractController
{
    #[Route('/', name: 'app_admin_annonces', methods: ['GET'])]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        return $this->render('admin/annonce/index.html.twig', [
            'annonces' => $annonceRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_annonce_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Annonce $annonce, EntityManagerInterface $entityManager, TrajetRepository $trajetRepository, UtilisateurRepository $utilisateurRepository): Response
    {
        // Récupération des trajets et utilisateurs pour le formulaire de modification
        $trajets = $trajetRepository->findAll();
        $conducteurs = $utilisateurRepository->findConducteurs();

        if ($request->isMethod('POST')) {
            $annonce->setTitre($request->request->get('titre'));
            $annonce->setDescription($request->request->get('description'));
            $annonce->setAvailableSeats($request->request->get('seats'));
            $annonce->setDepartureDate(new \DateTime($request->request->get('departure_date')));
            
            // Mise à jour du statut
            $status = $request->request->get('status');
            if ($status) {
                $annonce->setStatus($status);
            }
            
            // Mise à jour du trajet
            $trajetId = $request->request->get('trajet_id');
            if ($trajetId) {
                $trajet = $trajetRepository->find($trajetId);
                if ($trajet) {
                    $annonce->setTrajet($trajet);
                }
            }
            
            // Mise à jour du conducteur
            $conducteurId = $request->request->get('conducteur_id');
            if ($conducteurId) {
                $conducteur = $utilisateurRepository->find($conducteurId);
                if ($conducteur) {
                    $annonce->setDriverId($conducteur->getId());
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'annonce a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_annonces');
        }

        return $this->render('admin/annonce/edit.html.twig', [
            'annonce' => $annonce,
            'trajets' => $trajets,
            'conducteurs' => $conducteurs,
        ]);
    }

    #[Route('/{id}/terminate', name: 'app_admin_annonce_terminate', methods: ['GET', 'POST'])]
    public function terminate(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        // Changer le statut de l'annonce à "terminé"
        $annonce->setStatus('terminé');
        
        // Ajout de la date de terminaison
        $annonce->setDateTermination(new \DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'L\'annonce a été terminée avec succès.');
        
        return $this->redirectToRoute('app_admin_annonces');
    }

    #[Route('/{id}/delete', name: 'app_admin_annonce_delete', methods: ['POST'])]
    public function delete(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->request->get('_token'))) {
            $entityManager->remove($annonce);
            $entityManager->flush();
            $this->addFlash('success', 'L\'annonce a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_annonces', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_admin_annonce_show', methods: ['GET'])]
    public function show(Annonce $annonce): Response
    {
        return $this->render('admin/annonce/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }
} 