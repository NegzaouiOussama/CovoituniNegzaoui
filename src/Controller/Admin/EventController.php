<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/events')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_admin_events', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('admin/event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        
        if ($request->isMethod('POST')) {
            $event->setTypeEvent($request->request->get('type_event'));
            $event->setNom($request->request->get('nom'));
            $event->setDateEvent(new \DateTime($request->request->get('date_event')));
            $event->setHeureEvent($request->request->get('heure_event'));
            $event->setLieu($request->request->get('lieu'));
            $event->setDescription($request->request->get('description'));
            $event->setStatus($request->request->get('status', 'ACTIVE'));
            
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'événement a été créé avec succès.');
            return $this->redirectToRoute('app_admin_events');
        }

        return $this->render('admin/event/new.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{idEvent}/edit', name: 'app_admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $event->setTypeEvent($request->request->get('type_event'));
            $event->setNom($request->request->get('nom'));
            $event->setDateEvent(new \DateTime($request->request->get('date_event')));
            $event->setHeureEvent($request->request->get('heure_event'));
            $event->setLieu($request->request->get('lieu'));
            $event->setDescription($request->request->get('description'));
            $event->setStatus($request->request->get('status'));
            
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'événement a été mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_events');
        }

        return $this->render('admin/event/edit.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{idEvent}/delete', name: 'app_admin_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getIdEvent(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_events', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{idEvent}', name: 'app_admin_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('admin/event/show.html.twig', [
            'event' => $event,
        ]);
    }
} 