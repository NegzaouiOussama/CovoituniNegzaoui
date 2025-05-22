<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(
            ['status' => 'ACTIVE'],
            ['dateEvent' => 'ASC']
        );

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/{id}', name: 'app_events_show')]
    public function show(int $id, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }
}