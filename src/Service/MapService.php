<?php

namespace App\Service;

use App\Entity\Trajet;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MapService
{
    private $httpClient;
    private $tunisianBounds = [
        'minLat' => 30.0, // Limite sud
        'maxLat' => 38.0, // Limite nord
        'minLng' => 7.0,  // Limite ouest
        'maxLng' => 12.0  // Limite est
    ];
    private $tunisianCities = [
        'tunis', 'sfax', 'sousse', 'kairouan', 'bizerte', 'gabes', 'ariana', 
        'gafsa', 'monastir', 'ben arous', 'kasserine', 'médenine', 'nabeul', 
        'tataouine', 'mahdia', 'béja', 'jendouba', 'el kef', 'siliana', 
        'zaghouan', 'kebili', 'tozeur', 'manouba', 'sidi bouzid', 'carthage', 
        'la marsa', 'hammamet', 'zarzis', 'djerba', 'tabarka', 'rades', 
        'mégrine', 'la goulette', 'douar hicher', 'msaken', 'korba', 'menzel temime'
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Récupère les données de carte pour un trajet
     */
    public function getTrajetMapData(Trajet $trajet): array
    {
        // Géocode le point de départ et d'arrivée
        $departurePoint = $this->geocodeLocation($trajet->getDeparturePoint());
        $arrivalPoint = $this->geocodeLocation($trajet->getArrivalPoint());
        
        if (!$departurePoint || !$arrivalPoint) {
            throw new \Exception('Impossible de trouver les coordonnées des lieux spécifiés');
        }
        
        // Vérifier que les lieux sont en Tunisie
        if (!$this->isInTunisia($departurePoint['lat'], $departurePoint['lon']) || 
            !$this->isInTunisia($arrivalPoint['lat'], $arrivalPoint['lon'])) {
            throw new \Exception('Les lieux spécifiés ne se trouvent pas en Tunisie');
        }
        
        // Calcul de l'itinéraire
        $route = $this->getRoute($departurePoint, $arrivalPoint);
        
        return [
            'departurePoint' => $departurePoint,
            'arrivalPoint' => $arrivalPoint,
            'route' => $route['route'],
            'distance' => $route['distance'],
            'duration' => $route['duration']
        ];
    }

    /**
     * Géocode un lieu en utilisant Nominatim (OpenStreetMap)
     */
    public function geocodeLocation(string $location): ?array
    {
        // Vérifier si le lieu est valide pour la Tunisie
        if (!$this->isValidTunisianLocation($location)) {
            throw new \Exception('Cette ville n\'existe pas en Tunisie');
        }
        
        // Ajouter "Tunisie" à la recherche pour améliorer la précision
        $query = urlencode($location . ', Tunisie');
        
        $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'tn' // Limiter à la Tunisie
            ],
            'headers' => [
                'User-Agent' => 'CovoitUni/1.0'
            ]
        ]);
        
        $data = $response->toArray();
        
        if (empty($data)) {
            throw new \Exception('Cette ville n\'existe pas en Tunisie');
        }
        
        $result = $data[0];
        
        // Vérifier que le résultat est en Tunisie
        if (!$this->isInTunisia((float)$result['lat'], (float)$result['lon'])) {
            throw new \Exception('Cette ville n\'existe pas en Tunisie');
        }
        
        return [
            'lat' => (float)$result['lat'],
            'lon' => (float)$result['lon'],
            'display_name' => $result['display_name']
        ];
    }

    /**
     * Valide si un lieu est en Tunisie
     */
    public function isValidTunisianLocation(string $location): bool
    {
        // Nettoyage et mise en minuscule
        $location = strtolower(trim($location));
        
        // Vérifier si le lieu contient au moins 2 caractères
        if (strlen($location) < 2) {
            return false;
        }
        
        // Vérification rapide - si le lieu est dans notre liste des villes tunisiennes
        foreach ($this->tunisianCities as $city) {
            if (strpos($location, $city) !== false || strpos($city, $location) !== false) {
                return true;
            }
        }
        
        // Si ce n'est pas dans notre liste, essayons une géocodage limité à la Tunisie
        try {
            $query = urlencode($location . ', Tunisie');
            
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tn' // Limiter à la Tunisie
                ],
                'headers' => [
                    'User-Agent' => 'CovoitUni/1.0'
                ]
            ]);
            
            $data = $response->toArray();
            
            if (empty($data)) {
                return false;
            }
            
            // Vérifier que les coordonnées sont en Tunisie
            $result = $data[0];
            return $this->isInTunisia((float)$result['lat'], (float)$result['lon']);
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si les coordonnées se trouvent en Tunisie
     */
    private function isInTunisia(float $lat, float $lon): bool
    {
        return $lat >= $this->tunisianBounds['minLat'] && 
               $lat <= $this->tunisianBounds['maxLat'] && 
               $lon >= $this->tunisianBounds['minLng'] && 
               $lon <= $this->tunisianBounds['maxLng'];
    }

    /**
     * Calcul de l'itinéraire, la distance et la durée entre deux points
     */
    private function getRoute(array $start, array $end): array
    {
        $response = $this->httpClient->request('GET', 'https://router.project-osrm.org/route/v1/driving/' . 
            $start['lon'] . ',' . $start['lat'] . ';' . $end['lon'] . ',' . $end['lat'], [
            'query' => [
                'overview' => 'full',
                'geometries' => 'polyline',
                'steps' => 'true'
            ]
        ]);
        
        $data = $response->toArray();
        
        if (!isset($data['routes']) || empty($data['routes'])) {
            throw new \Exception('Impossible de calculer l\'itinéraire');
        }
        
        $route = $data['routes'][0];
        
        // Convertir en kilomètres et heures/minutes
        $distanceKm = round($route['distance'] / 1000, 1);
        $durationMinutes = round($route['duration'] / 60);
        $durationHours = floor($durationMinutes / 60);
        $durationRemainingMinutes = $durationMinutes % 60;
        
        $durationFormatted = '';
        if ($durationHours > 0) {
            $durationFormatted .= $durationHours . ' h ';
        }
        $durationFormatted .= $durationRemainingMinutes . ' min';
        
        return [
            'route' => $route['geometry'],
            'distance' => $distanceKm,
            'duration' => $durationFormatted
        ];
    }
} 