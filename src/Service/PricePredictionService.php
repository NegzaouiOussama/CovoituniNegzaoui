<?php

namespace App\Service;

use App\Entity\Trajet;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PricePredictionService
{
    private $mapService;
    
    public function __construct(MapService $mapService)
    {
        $this->mapService = $mapService;
    }
    
    /**
     * Prédit le prix recommandé pour un trajet
     */
    public function predictPrice(string $departurePoint, string $arrivalPoint): array
    {
        try {
            // Obtenir la distance en utilisant le MapService existant
            $departureGeo = $this->mapService->geocodeLocation($departurePoint);
            $arrivalGeo = $this->mapService->geocodeLocation($arrivalPoint);
            
            $route = $this->mapService->getRoute($departureGeo, $arrivalGeo);
            $distance = $route['distance'];
            
            // Appeler le script Python pour la prédiction
            $predictedPrice = $this->callPythonPredictor($distance, $departurePoint, $arrivalPoint);
            
            return [
                'success' => true,
                'price' => $predictedPrice,
                'distance' => $distance,
                'unit' => 'DT',
                'message' => sprintf('Prix recommandé pour %s → %s (%s km): %s DT', 
                    $departurePoint, $arrivalPoint, $distance, $predictedPrice)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la prédiction du prix: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enregistre les données d'un trajet dans l'historique
     */
    public function recordTripData(Trajet $trajet, float $distance): bool
    {
        try {
            $price = $trajet->getPrice();
            $departurePoint = $trajet->getDeparturePoint();
            $arrivalPoint = $trajet->getArrivalPoint();
            
            // Appeler le script Python pour enregistrer les données
            $this->recordTripDataPython($departurePoint, $arrivalPoint, $distance, $price);
            
            return true;
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas la propager
            error_log('Erreur lors de l\'enregistrement des données de trajet: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Appelle le script Python pour prédire le prix
     */
    private function callPythonPredictor(float $distance, string $from = null, string $to = null): float
    {
        $pythonScript = <<<PYTHON
import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from AI.price_predictor import PricePredictor

# Initialiser le prédicteur
predictor = PricePredictor()

# Faire la prédiction
price = predictor.predict_price($distance, origin="$from", destination="$to")

# Retourner le résultat
print(price)
PYTHON;

        // Créer un fichier temporaire pour le script
        $tmpScript = tempnam(sys_get_temp_dir(), 'price_pred_') . '.py';
        file_put_contents($tmpScript, $pythonScript);

        try {
            // Exécuter le script Python
            $process = new Process(['python', $tmpScript]);
            $process->run();

            // Vérifier les erreurs
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Récupérer le résultat
            $result = trim($process->getOutput());
            return (float) $result;
        } finally {
            // Nettoyer le fichier temporaire
            @unlink($tmpScript);
        }
    }
    
    /**
     * Enregistre les données d'un trajet dans le système Python
     */
    private function recordTripDataPython(string $from, string $to, float $distance, float $price): void
    {
        $pythonScript = <<<PYTHON
import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from AI.price_predictor import PricePredictor

# Initialiser le prédicteur
predictor = PricePredictor()

# Ajouter les données du trajet
predictor.add_trip_data("$from", "$to", $distance, $price)

# Retourner le résultat
print("success")
PYTHON;

        // Créer un fichier temporaire pour le script
        $tmpScript = tempnam(sys_get_temp_dir(), 'price_record_') . '.py';
        file_put_contents($tmpScript, $pythonScript);

        try {
            // Exécuter le script Python
            $process = new Process(['python', $tmpScript]);
            $process->run();

            // Vérifier les erreurs
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } finally {
            // Nettoyer le fichier temporaire
            @unlink($tmpScript);
        }
    }
} 