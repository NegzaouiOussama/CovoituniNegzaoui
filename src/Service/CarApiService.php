<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CarApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = '12GmUrLap23BZ09Js80jA'; // API key provided by user
    }

    /**
     * Get all car makes from the Carbon Interface API
     */
    public function getCarMakes(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.carboninterface.com/api/v1/vehicle_makes', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response->toArray();
            
            // Sort makes alphabetically
            usort($data, function($a, $b) {
                return $a['data']['attributes']['name'] <=> $b['data']['attributes']['name'];
            });
            
            return $data;
        } catch (\Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }

    /**
     * Get all car models for a specific make from the Carbon Interface API
     */
    public function getCarModels(string $makeId): array
    {
        try {
            $response = $this->httpClient->request('GET', "https://www.carboninterface.com/api/v1/vehicle_makes/{$makeId}/vehicle_models", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = $response->toArray();
            
            // Sort models alphabetically
            usort($data, function($a, $b) {
                return $a['data']['attributes']['name'] <=> $b['data']['attributes']['name'];
            });
            
            return $data;
        } catch (\Exception $e) {
            // Return empty array in case of error
            return [];
        }
    }
} 