<?php

namespace App\Controller\Admin;

use App\Entity\AnnonceEvent;
use App\Repository\AnnonceEventRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/annonces-event')]
class AnnonceEventController extends AbstractController
{
    #[Route('/', name: 'app_admin_annonces_event', methods: ['GET'])]
    public function index(AnnonceEventRepository $annonceEventRepository): Response
    {
        return $this->render('admin/annonce_event/index.html.twig', [
            'annonces' => $annonceEventRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_annonce_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        AnnonceEvent $annonceEvent, 
        EntityManagerInterface $entityManager, 
        EventRepository $eventRepository, 
        UserRepository $userRepository,
        CarRepository $carRepository
    ): Response {
        // Récupération des événements, utilisateurs et voitures pour le formulaire
        $events = $eventRepository->findAll();
        $conducteurs = $userRepository->findByRole('ROLE_CONDUCTEUR');
        $cars = $carRepository->findAll();

        if ($request->isMethod('POST')) {
            $annonceEvent->setTitre($request->request->get('titre'));
            $annonceEvent->setDescription($request->request->get('description'));
            $annonceEvent->setDeparturePoint($request->request->get('departure_point'));
            $annonceEvent->setArrivalPoint($request->request->get('arrival_point'));
            $annonceEvent->setPrix($request->request->get('prix'));
            $annonceEvent->setAvailableSeats($request->request->get('available_seats'));
            
            if ($request->request->get('departure_date')) {
                $annonceEvent->setDepartureDate(new \DateTime($request->request->get('departure_date')));
            }
            
            // Mise à jour de l'événement
            $eventId = $request->request->get('event_id');
            if ($eventId) {
                $event = $eventRepository->find($eventId);
                if ($event) {
                    $annonceEvent->setEvent($event);
                }
            }
            
            // Mise à jour du conducteur
            $conducteurId = $request->request->get('conducteur_id');
            if ($conducteurId) {
                $annonceEvent->setDriverId((int)$conducteurId);
            }
            
            // Mise à jour de la voiture
            $carId = $request->request->get('car_id');
            if ($carId) {
                $annonceEvent->setCarId((int)$carId);
            }
            
            // Mise à jour du statut
            $status = $request->request->get('status');
            if ($status) {
                $annonceEvent->setStatus($status);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'annonce d\'événement a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_annonces_event');
        }

        return $this->render('admin/annonce_event/edit.html.twig', [
            'annonce' => $annonceEvent,
            'events' => $events,
            'conducteurs' => $conducteurs,
            'cars' => $cars,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_annonce_event_delete', methods: ['POST'])]
    public function delete(Request $request, AnnonceEvent $annonceEvent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$annonceEvent->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des réservations associées
            $reservations = $annonceEvent->getReservations();
            
            if (count($reservations) > 0) {
                // Si des réservations existent, supprimer d'abord les réservations
                foreach ($reservations as $reservation) {
                    $entityManager->remove($reservation);
                }
            }
            
            $entityManager->remove($annonceEvent);
            $entityManager->flush();
            $this->addFlash('success', 'L\'annonce d\'événement a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_annonces_event', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_admin_annonce_event_show', methods: ['GET'])]
    public function show(AnnonceEvent $annonceEvent): Response
    {
        return $this->render('admin/annonce_event/show.html.twig', [
            'annonce' => $annonceEvent,
        ]);
    }
} 