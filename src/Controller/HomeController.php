<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AnnonceRepository;
use App\Repository\EventRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(AnnonceRepository $annonceRepository, EventRepository $eventRepository): Response
    {
        // Get latest rides
        $latestRides = $annonceRepository->findBy(
            ['status' => 'OPEN'],
            ['date_publication' => 'DESC'],
            3
        );

        // Get upcoming events
        $upcomingEvents = $eventRepository->findBy(
            ['status' => 'ACTIVE'],
            ['dateEvent' => 'ASC'],
            3
        );

        return $this->render('home/index.html.twig', [
            'latest_rides' => $latestRides,
            'upcoming_events' => $upcomingEvents,
        ]);
    }
} 