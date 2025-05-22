<?php

namespace App\Controller;

use App\Service\PricePredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PriceController extends AbstractController
{
    #[Route('/api/predict-price', name: 'app_api_predict_price')]
    public function predictPrice(Request $request, PricePredictionService $pricePredictionService): JsonResponse
    {
        $departurePoint = $request->query->get('from');
        $arrivalPoint = $request->query->get('to');
        
        if (!$departurePoint || !$arrivalPoint) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Les paramètres "from" et "to" sont requis'
            ], 400);
        }
        
        $result = $pricePredictionService->predictPrice($departurePoint, $arrivalPoint);
        
        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }
} 