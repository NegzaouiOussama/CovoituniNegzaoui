<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\AnnonceRepository;
use App\Repository\AnnonceEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_admin_reservations', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Reservation $reservation, 
        EntityManagerInterface $entityManager, 
        UtilisateurRepository $userRepository,
        AnnonceRepository $annonceRepository,
        AnnonceEventRepository $annonceEventRepository
    ): Response {
        $users = $userRepository->findAll();
        $annonces = $annonceRepository->findAll();
        $annonceEvents = $annonceEventRepository->findAll();

        if ($request->isMethod('POST')) {
            $oldStatus = $reservation->getStatus();
            $newStatus = $request->request->get('status');
            
            // Mise à jour du statut
            if ($newStatus) {
                $reservation->setStatus($newStatus);
                // Mise à jour de la date de modification
                $reservation->setUpdatedAt(new \DateTime());
            }
            
            // Mise à jour de l'utilisateur
            $userId = $request->request->get('user_id');
            if ($userId) {
                $reservation->setUserId($userId);
            }
            
            // Mise à jour de l'annonce normale ou événement selon le type
            if ($reservation->getType() === 'TRAJET') {
                $annonceId = $request->request->get('annonce_id');
                if ($annonceId) {
                    $annonce = $annonceRepository->find($annonceId);
                    if ($annonce) {
                        $oldAnnonce = $reservation->getAnnonce();
                        
                        // Si l'annonce a changé, on modifie les places disponibles
                        if ($oldAnnonce && $oldAnnonce->getId() !== $annonce->getId()) {
                            // On augmente les places de l'ancienne annonce
                            if (in_array($oldStatus, ['PENDING', 'ACCEPTED'])) {
                                $oldAnnonce->setAvailableSeats($oldAnnonce->getAvailableSeats() + 1);
                                $entityManager->persist($oldAnnonce);
                            }
                            
                            // On diminue les places de la nouvelle annonce
                            if (in_array($newStatus, ['PENDING', 'ACCEPTED'])) {
                                $annonce->setAvailableSeats($annonce->getAvailableSeats() - 1);
                                $entityManager->persist($annonce);
                            }
                        }
                        
                        $reservation->setAnnonce($annonce);
                    }
                }
            } elseif ($reservation->getType() === 'EVENT') {
                $annonceEventId = $request->request->get('annonce_event_id');
                if ($annonceEventId) {
                    $annonceEvent = $annonceEventRepository->find($annonceEventId);
                    if ($annonceEvent) {
                        $oldAnnonceEvent = $reservation->getAnnonceEvent();
                        
                        // Si l'annonce événement a changé, on modifie les places disponibles
                        if ($oldAnnonceEvent && $oldAnnonceEvent->getId() !== $annonceEvent->getId()) {
                            // On augmente les places de l'ancienne annonce événement
                            if (in_array($oldStatus, ['PENDING', 'ACCEPTED'])) {
                                $oldAnnonceEvent->setAvailableSeats($oldAnnonceEvent->getAvailableSeats() + 1);
                                $entityManager->persist($oldAnnonceEvent);
                            }
                            
                            // On diminue les places de la nouvelle annonce événement
                            if (in_array($newStatus, ['PENDING', 'ACCEPTED'])) {
                                $annonceEvent->setAvailableSeats($annonceEvent->getAvailableSeats() - 1);
                                $entityManager->persist($annonceEvent);
                            }
                        }
                        
                        $reservation->setAnnonceEvent($annonceEvent);
                    }
                }
            }
            
            // Mise à jour du commentaire
            $comment = $request->request->get('comment');
            $reservation->setComment($comment);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'La réservation a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_reservations');
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'users' => $users,
            'annonces' => $annonces,
            'annonceEvents' => $annonceEvents,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            // Augmenter le nombre de places disponibles si la réservation est en attente ou acceptée
            if (in_array($reservation->getStatus(), ['PENDING', 'ACCEPTED'])) {
                // Vérifier si c'est une réservation pour une annonce standard
                if ($reservation->getType() === 'TRAJET' && $reservation->getAnnonce()) {
                    $annonce = $reservation->getAnnonce();
                    $annonce->setAvailableSeats($annonce->getAvailableSeats() + 1);
                    $entityManager->persist($annonce);
                }
                // Vérifier si c'est une réservation pour une annonce événement
                elseif ($reservation->getType() === 'EVENT' && $reservation->getAnnonceEvent()) {
                    $annonceEvent = $reservation->getAnnonceEvent();
                    $annonceEvent->setAvailableSeats($annonceEvent->getAvailableSeats() + 1);
                    $entityManager->persist($annonceEvent);
                }
            }
            
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée avec succès et les places ont été libérées.');
        }

        return $this->redirectToRoute('app_admin_reservations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_admin_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
} 