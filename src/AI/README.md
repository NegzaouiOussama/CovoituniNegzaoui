# Système de prédiction de prix IA pour CovoitUni

Ce module utilise l'intelligence artificielle pour suggérer des prix optimaux pour les trajets de covoiturage en Tunisie.

## Fonctionnalités

- Prédiction de prix basée sur la distance entre deux villes
- Recommandations adaptées aux trajets populaires en Tunisie
- Apprentissage continu basé sur les données historiques des trajets
- API simple pour l'intégration avec le reste de l'application

## Installation

1. Assurez-vous que Python 3.7+ est installé sur votre système
2. Installez les dépendances requises:

```bash
pip install -r src/AI/requirements.txt
```

## Comment ça marche

Le système combine plusieurs approches pour proposer un prix optimal:

1. **Base de connaissances**: Une liste des trajets populaires en Tunisie avec leurs prix moyens
2. **Modèle adaptatif**: Un algorithme qui apprend des nouveaux trajets ajoutés dans l'application
3. **Calcul de distance**: Utilisation de la distance pour estimer un prix au kilomètre raisonnable

Chaque fois qu'un nouveau trajet est créé dans l'application, ses données sont enregistrées pour améliorer les futures prédictions.

## Intégration avec Symfony

Le système est intégré à l'application Symfony via:

- `PricePredictionService.php`: Service qui fait le pont entre l'application et le module Python
- `PriceController.php`: Contrôleur qui expose l'API de prédiction
- Interface utilisateur dans le formulaire d'ajout de trajet

## Exemple d'utilisation

Dans l'interface utilisateur:
1. Sélectionnez un point de départ et une destination
2. Cliquez sur "Suggestion IA"
3. Le système analysera le trajet et proposera un prix optimal

Via l'API:
```
GET /api/predict-price?from=tunis&to=nabeul
```

Réponse:
```json
{
  "success": true,
  "price": 7,
  "distance": 66.7,
  "unit": "DT",
  "message": "Prix recommandé pour tunis → nabeul (66.7 km): 7 DT"
}
``` 