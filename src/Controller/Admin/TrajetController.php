<?php

namespace App\Controller\Admin;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/trajets')]
class TrajetController extends AbstractController
{
    #[Route('/', name: 'app_admin_trajets', methods: ['GET'])]
    public function index(TrajetRepository $trajetRepository): Response
    {
        return $this->render('admin/trajet/index.html.twig', [
            'trajets' => $trajetRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_trajet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $trajet->setDeparturePoint($request->request->get('departure_point'));
            $trajet->setArrivalPoint($request->request->get('arrival_point'));
            $trajet->setPrice($request->request->get('price'));
            
            if ($request->request->has('titre') && method_exists($trajet, 'setTitre')) {
                $trajet->setTitre($request->request->get('titre'));
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Le trajet a été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_trajets');
        }

        return $this->render('admin/trajet/edit.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_trajet_delete', methods: ['POST'])]
    public function delete(Request $request, Trajet $trajet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$trajet->getId(), $request->request->get('_token'))) {
            $entityManager->remove($trajet);
            $entityManager->flush();
            $this->addFlash('success', 'Le trajet a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_trajets', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_admin_trajet_show', methods: ['GET'])]
    public function show(Trajet $trajet): Response
    {
        return $this->render('admin/trajet/show.html.twig', [
            'trajet' => $trajet,
        ]);
    }
} 