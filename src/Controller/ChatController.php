<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Repository\ChatMessageRepository;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/conversations', name: 'app_chat_conversations')]
    public function conversations(ChatMessageRepository $chatMessageRepository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos conversations');
        }
        
        $conversations = $chatMessageRepository->findConversations($user->getId());
        $unreadCount = $chatMessageRepository->countUnreadMessages($user->getId());
        
        return $this->render('chat/conversations.html.twig', [
            'conversations' => $conversations,
            'unreadCount' => $unreadCount,
        ]);
    }
    
    #[Route('/conversation/{reservationId}/{type}', name: 'app_chat_conversation')]
    public function conversation(
        int $reservationId, 
        string $type, 
        ChatMessageRepository $chatMessageRepository,
        ReservationRepository $reservationRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette conversation');
        }
        
        // Vérifier que la réservation existe
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation) {
            throw $this->createNotFoundException('Cette réservation n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est bien le passager ou le conducteur de la réservation
        $isPassenger = $reservation->getUserId() == $user->getId();
        $isDriver = false;
        
        if ($type === 'TRAJET' && $reservation->getAnnonce()) {
            $isDriver = $reservation->getAnnonce()->getDriverId() == $user->getId();
        } elseif ($type === 'EVENT' && $reservation->getAnnonceEvent()) {
            $isDriver = $reservation->getAnnonceEvent()->getDriverId() == $user->getId();
        }
        
        if (!$isPassenger && !$isDriver) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette conversation');
        }
        
        // Vérifier que la réservation est bien acceptée
        if ($reservation->getStatus() !== 'ACCEPTED') {
            throw $this->createAccessDeniedException('Le chat n\'est disponible que pour les réservations acceptées');
        }
        
        // Récupérer l'autre utilisateur (passager ou conducteur)
        $otherUserId = $isPassenger 
            ? ($type === 'TRAJET' ? $reservation->getAnnonce()->getDriverId() : $reservation->getAnnonceEvent()->getDriverId())
            : $reservation->getUserId();
        
        $otherUser = $utilisateurRepository->find($otherUserId);
        
        // Marquer les messages comme lus
        $chatMessageRepository->markAsRead($reservationId, $type, $user->getId());
        
        // Récupérer les messages de la conversation
        $messages = $chatMessageRepository->findByReservation($reservationId, $type);
        
        return $this->render('chat/conversation.html.twig', [
            'reservation' => $reservation,
            'messages' => $messages,
            'otherUser' => $otherUser,
            'currentUser' => $user,
            'type' => $type,
        ]);
    }
    
    #[Route('/send/{reservationId}/{type}', name: 'app_chat_send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        int $reservationId, 
        string $type, 
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour envoyer un message');
        }
        
        // Vérifier que la réservation existe
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation) {
            throw $this->createNotFoundException('Cette réservation n\'existe pas');
        }
        
        // Vérifier que l'utilisateur est bien le passager ou le conducteur de la réservation
        $isPassenger = $reservation->getUserId() == $user->getId();
        $isDriver = false;
        
        if ($type === 'TRAJET' && $reservation->getAnnonce()) {
            $isDriver = $reservation->getAnnonce()->getDriverId() == $user->getId();
        } elseif ($type === 'EVENT' && $reservation->getAnnonceEvent()) {
            $isDriver = $reservation->getAnnonceEvent()->getDriverId() == $user->getId();
        }
        
        if (!$isPassenger && !$isDriver) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à envoyer un message dans cette conversation');
        }
        
        // Vérifier que la réservation est bien acceptée
        if ($reservation->getStatus() !== 'ACCEPTED') {
            throw $this->createAccessDeniedException('Le chat n\'est disponible que pour les réservations acceptées');
        }
        
        // Récupérer le message
        $content = $request->request->get('message');
        if (!$content) {
            // Si la requête est une requête AJAX, renvoyer une réponse JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le message ne peut pas être vide'
                ]);
            }
            
            // Sinon, rediriger avec un message flash
            $this->addFlash('error', 'Le message ne peut pas être vide');
            return $this->redirectToRoute('app_chat_conversation', [
                'reservationId' => $reservationId,
                'type' => $type,
            ]);
        }
        
        // Récupérer l'ID du destinataire
        $receiverId = $isPassenger 
            ? ($type === 'TRAJET' ? $reservation->getAnnonce()->getDriverId() : $reservation->getAnnonceEvent()->getDriverId())
            : $reservation->getUserId();
        
        // Créer et sauvegarder le message
        $message = new ChatMessage();
        $message->setSenderId($user->getId());
        $message->setReceiverId($receiverId);
        $message->setReservationId($reservationId);
        $message->setReservationType($type);
        $message->setContent($content);
        
        $entityManager->persist($message);
        $entityManager->flush();
        
        // Si la requête est une requête AJAX, renvoyer une réponse JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'senderId' => $message->getSenderId(),
                    'receiverId' => $message->getReceiverId(),
                    'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        }
        
        // Sinon, rediriger vers la conversation
        return $this->redirectToRoute('app_chat_conversation', [
            'reservationId' => $reservationId,
            'type' => $type,
        ]);
    }
    
    #[Route('/get-new-messages/{reservationId}/{type}/{lastId}', name: 'app_chat_get_new_messages', methods: ['GET'])]
    public function getNewMessages(
        int $reservationId,
        string $type,
        int $lastId,
        ChatMessageRepository $chatMessageRepository,
        ReservationRepository $reservationRepository
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour récupérer les messages'], 403);
        }
        
        // Vérifier que la réservation existe
        $reservation = $reservationRepository->find($reservationId);
        if (!$reservation) {
            return $this->json(['error' => 'Cette réservation n\'existe pas'], 404);
        }
        
        // Vérifier que l'utilisateur est bien le passager ou le conducteur de la réservation
        $isPassenger = $reservation->getUserId() == $user->getId();
        $isDriver = false;
        
        if ($type === 'TRAJET' && $reservation->getAnnonce()) {
            $isDriver = $reservation->getAnnonce()->getDriverId() == $user->getId();
        } elseif ($type === 'EVENT' && $reservation->getAnnonceEvent()) {
            $isDriver = $reservation->getAnnonceEvent()->getDriverId() == $user->getId();
        }
        
        if (!$isPassenger && !$isDriver) {
            return $this->json(['error' => 'Vous n\'êtes pas autorisé à accéder à cette conversation'], 403);
        }
        
        // Récupérer les nouveaux messages
        $newMessages = $chatMessageRepository->findNewerThan($reservationId, $type, $lastId);
        
        // Marquer les messages comme lus si l'utilisateur est le destinataire
        $chatMessageRepository->markAsRead($reservationId, $type, $user->getId());
        
        // Formater les messages pour la réponse JSON
        $formattedMessages = [];
        foreach ($newMessages as $message) {
            $formattedMessages[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'senderId' => $message->getSenderId(),
                'receiverId' => $message->getReceiverId(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }
        
        // Log pour le débogage
        $this->get('logger')->info('Nouveaux messages récupérés', [
            'reservationId' => $reservationId,
            'type' => $type,
            'lastId' => $lastId,
            'messageCount' => count($newMessages),
            'isPassenger' => $isPassenger,
            'isDriver' => $isDriver,
            'userId' => $user->getId()
        ]);
        
        return $this->json([
            'success' => true,
            'messages' => $formattedMessages,
            'debug' => [
                'reservationId' => $reservationId,
                'type' => $type,
                'lastId' => $lastId,
                'user' => [
                    'id' => $user->getId(),
                    'role' => $user->getRoleCode()
                ],
                'isPassenger' => $isPassenger,
                'isDriver' => $isDriver
            ]
        ]);
    }
} 