<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RideController extends AbstractController
{
    #[Route('/rides', name: 'app_rides')]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        $rides = $annonceRepository->findBy(
            ['status' => 'OPEN'],
            ['date_publication' => 'DESC']
        );

        return $this->render('ride/index.html.twig', [
            'rides' => $rides,
        ]);
    }

    #[Route('/rides/{id}', name: 'app_rides_show')]
    public function show(int $id, AnnonceRepository $annonceRepository): Response
    {
        $ride = $annonceRepository->find($id);

        if (!$ride) {
            throw $this->createNotFoundException('Ride not found');
        }

        return $this->render('ride/show.html.twig', [
            'ride' => $ride,
        ]);
    }
} 