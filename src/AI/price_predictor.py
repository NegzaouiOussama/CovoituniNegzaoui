import os
import json
import numpy as np
from datetime import datetime
import pickle

class PricePredictor:
    """
    Un modèle d'IA simple pour prédire les prix de trajets basé sur 
    la distance et les données historiques
    """
    
    def __init__(self):
        self.model_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'price_model.pkl')
        self.data_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'historical_trips.json')
        self.common_routes = {
            "tunis_nabeul": {"distance": 66.7, "avg_price": 7.0},
            "tunis_sousse": {"distance": 143.0, "avg_price": 15.0},
            "tunis_sfax": {"distance": 270.0, "avg_price": 25.0},
            "tunis_kairouan": {"distance": 156.0, "avg_price": 16.0},
            "tunis_bizerte": {"distance": 65.0, "avg_price": 7.0},
            "sousse_sfax": {"distance": 133.0, "avg_price": 12.0},
            "sousse_monastir": {"distance": 23.0, "avg_price": 3.0},
            "tunis_hammamet": {"distance": 65.0, "avg_price": 6.5},
        }
        
        # Charger ou créer le modèle
        self._load_or_train_model()
    
    def _load_historical_data(self):
        """Charge les données historiques des trajets"""
        try:
            if os.path.exists(self.data_file):
                with open(self.data_file, 'r') as f:
                    return json.load(f)
            return []
        except Exception as e:
            print(f"Erreur lors du chargement des données: {e}")
            return []
    
    def _save_historical_data(self, data):
        """Enregistre les données historiques des trajets"""
        try:
            with open(self.data_file, 'w') as f:
                json.dump(data, f, indent=2)
        except Exception as e:
            print(f"Erreur lors de l'enregistrement des données: {e}")
    
    def _load_or_train_model(self):
        """Charge le modèle existant ou en entraîne un nouveau"""
        try:
            if os.path.exists(self.model_file):
                with open(self.model_file, 'rb') as f:
                    self.model = pickle.load(f)
                print("Modèle chargé avec succès")
            else:
                self._train_model()
        except Exception as e:
            print(f"Erreur lors du chargement du modèle: {e}")
            self._train_model()
    
    def _train_model(self):
        """
        Entraîne un modèle simple basé sur la régression linéaire
        en utilisant les données de trajets connus
        """
        # Créer un modèle simple basé sur les trajets connus
        # Dans un cas réel, nous utiliserions scikit-learn
        
        # Pour ce modèle simple, nous allons utiliser un coefficient 
        # qui représente le prix au kilomètre
        trips = self._load_historical_data()
        
        # Si nous n'avons pas assez de données, utiliser les routes communes
        if len(trips) < 5:
            distances = []
            prices = []
            for route, data in self.common_routes.items():
                distances.append(data["distance"])
                prices.append(data["avg_price"])
        else:
            distances = [trip["distance"] for trip in trips]
            prices = [trip["price"] for trip in trips]
        
        # Coefficient moyen (prix par km)
        if len(distances) > 0:
            coefficients = [p/d for p, d in zip(prices, distances) if d > 0]
            avg_coefficient = sum(coefficients) / len(coefficients)
        else:
            # Valeur par défaut: environ 0.10 DT par km
            avg_coefficient = 0.10
        
        # Ajouter un léger offset pour les frais fixes
        fixed_cost = 0.5
        
        # Notre modèle simple: price = distance * coefficient + fixed_cost
        self.model = {
            "coefficient": avg_coefficient,
            "fixed_cost": fixed_cost
        }
        
        # Enregistrer le modèle
        try:
            with open(self.model_file, 'wb') as f:
                pickle.dump(self.model, f)
            print("Modèle entraîné et enregistré avec succès")
        except Exception as e:
            print(f"Erreur lors de l'enregistrement du modèle: {e}")
    
    def _get_route_key(self, origin, destination):
        """Génère une clé standardisée pour une route"""
        if origin and destination:
            cities = sorted([origin.lower(), destination.lower()])
            return f"{cities[0]}_{cities[1]}"
        return None
    
    def predict_price(self, distance, origin=None, destination=None):
        """
        Prédit le prix d'un trajet basé sur la distance et, 
        si disponible, l'origine et la destination
        
        Args:
            distance: Distance en kilomètres
            origin: Ville de départ (optionnel)
            destination: Ville d'arrivée (optionnel)
            
        Returns:
            Le prix recommandé en DT
        """
        # Vérifier si c'est une route commune connue
        route_key = None
        if origin and destination:
            origin = origin.lower().replace(' ', '_')
            destination = destination.lower().replace(' ', '_')
            route_key = f"{origin}_{destination}"
            reverse_key = f"{destination}_{origin}"
            
            if route_key in self.common_routes:
                return self.common_routes[route_key]["avg_price"]
            elif reverse_key in self.common_routes:
                return self.common_routes[reverse_key]["avg_price"]
        
        # Sinon, utiliser notre modèle
        predicted_price = distance * self.model["coefficient"] + self.model["fixed_cost"]
        
        # Arrondir à 0.5 DT près
        predicted_price = round(predicted_price * 2) / 2
        
        # S'assurer que le prix n'est pas trop bas
        if predicted_price < 1:
            predicted_price = 1
            
        return predicted_price
    
    def add_trip_data(self, origin, destination, distance, price):
        """
        Ajoute les données d'un nouveau trajet à l'historique
        
        Args:
            origin: Ville de départ
            destination: Ville d'arrivée
            distance: Distance en kilomètres
            price: Prix en DT
        """
        trips = self._load_historical_data()
        
        new_trip = {
            "origin": origin.lower(),
            "destination": destination.lower(),
            "distance": distance,
            "price": price,
            "date": datetime.now().strftime("%Y-%m-%d")
        }
        
        trips.append(new_trip)
        self._save_historical_data(trips)
        
        # Réentraîner le modèle avec les nouvelles données
        self._train_model()
        
        return True


# Pour les tests
if __name__ == "__main__":
    predictor = PricePredictor()
    
    # Tester quelques prédictions
    test_trips = [
        {"origin": "Tunis", "destination": "Nabeul", "distance": 66.7},
        {"origin": "Sousse", "destination": "Monastir", "distance": 23},
        {"origin": "Tunis", "destination": "Sfax", "distance": 270},
        {"origin": "Gabes", "destination": "Tozeur", "distance": 235},
    ]
    
    for trip in test_trips:
        price = predictor.predict_price(
            trip["distance"], 
            origin=trip["origin"], 
            destination=trip["destination"]
        )
        print(f"{trip['origin']} → {trip['destination']} ({trip['distance']} km): {price} DT") 