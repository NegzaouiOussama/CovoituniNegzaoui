<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpGeoLocationService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get geolocation data from the user's IP address
     * 
     * @return array|null Location data or null if error
     */
    public function getLocationFromIp(): ?array
    {
        try {
            // Utiliser l'API sans clé (ip-api.com - gratuit avec une limite de 45 req/min)
            $response = $this->httpClient->request('GET', 'http://ip-api.com/json/');
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                // Vérifier si la requête a réussi
                if ($data['status'] === 'success') {
                    return [
                        'city' => $data['city'] ?? null,
                        'country' => $data['country'] ?? null,
                        'lat' => $data['lat'] ?? null,
                        'lon' => $data['lon'] ?? null
                    ];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            // En cas d'erreur, logger dans un environnement de production
            return null;
        }
    }
    
    /**
     * Get formatted location string (country, city)
     * 
     * @return string Formatted location string or empty string if error
     */
    public function getFormattedLocation(): string
    {
        $location = $this->getLocationFromIp();
        
        if ($location && isset($location['country']) && isset($location['city'])) {
            return $location['country'] . ', ' . $location['city'];
        }
        
        return '';
    }
} 