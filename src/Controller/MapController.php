<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Service\MapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapController extends AbstractController
{
    private $mapService;

    public function __construct(MapService $mapService)
    {
        $this->mapService = $mapService;
    }

    #[Route('/map/trajet/{id}', name: 'app_map_trajet')]
    public function showTrajetMap(Trajet $trajet): Response
    {
        try {
            // Récupérer les informations de la carte depuis le service
            $mapData = $this->mapService->getTrajetMapData($trajet);
            
            return $this->render('map/trajet.html.twig', [
                'trajet' => $trajet,
                'mapData' => $mapData,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_passager_liste_trajet');
        }
    }

    #[Route('/api/geocode', name: 'app_api_geocode')]
    public function geocode(Request $request): JsonResponse
    {
        $location = $request->query->get('location');
        
        try {
            $result = $this->mapService->geocodeLocation($location);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/api/validate-location', name: 'app_api_validate_location')]
    public function validateLocation(Request $request): JsonResponse
    {
        $location = $request->query->get('location');
        
        try {
            $isValid = $this->mapService->isValidTunisianLocation($location);
            return new JsonResponse(['valid' => $isValid]);
        } catch (\Exception $e) {
            return new JsonResponse(['valid' => false, 'error' => $e->getMessage()], 400);
        }
    }
} 