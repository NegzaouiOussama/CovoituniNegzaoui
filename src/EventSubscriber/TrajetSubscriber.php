<?php

namespace App\EventSubscriber;

use App\Entity\Trajet;
use App\Service\MapService;
use App\Service\PricePredictionService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TrajetSubscriber implements EventSubscriberInterface
{
    private $mapService;
    private $pricePredictionService;

    public function __construct(MapService $mapService, PricePredictionService $pricePredictionService)
    {
        $this->mapService = $mapService;
        $this->pricePredictionService = $pricePredictionService;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Trajet) {
            return;
        }

        try {
            // Obtenir la distance pour ce trajet
            $departureGeo = $this->mapService->geocodeLocation($entity->getDeparturePoint());
            $arrivalGeo = $this->mapService->geocodeLocation($entity->getArrivalPoint());
            
            $route = $this->mapService->getRoute($departureGeo, $arrivalGeo);
            $distance = $route['distance'];
            
            // Enregistrer les données pour entraîner le modèle
            $this->pricePredictionService->recordTripData($entity, $distance);
        } catch (\Exception $e) {
            // Juste logger l'erreur, ne pas interrompre le processus
            error_log('Erreur lors de l\'enregistrement des données pour l\'IA: ' . $e->getMessage());
        }
    }
} 