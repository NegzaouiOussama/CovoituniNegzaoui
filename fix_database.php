<?php
// Script pour corriger les problèmes d'auto-incrémentation dans la base de données

// Configuration pour la connexion à la base de données
$db_host = 'localhost';
$db_name = 'covoituni';
$db_user = 'root';
$db_pass = '';

try {
    // Connexion à la base de données MySQL
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n";
    
    // Désactiver les contraintes de clé étrangère
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. Réparer la table annonce_event
    echo "Réparation de la table annonce_event...\n";
    
    // Sauvegarder les données actuelles
    $pdo->exec("CREATE TABLE IF NOT EXISTS annonce_event_backup LIKE annonce_event");
    $pdo->exec("INSERT INTO annonce_event_backup SELECT * FROM annonce_event");
    
    // Corriger l'auto-incrémentation
    try {
        $pdo->exec("ALTER TABLE annonce_event MODIFY id INT AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE annonce_event AUTO_INCREMENT = 50");
        echo "Table annonce_event corrigée.\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de annonce_event: " . $e->getMessage() . "\n";
        
        // Si l'erreur est une duplication d'ID, recréer la table
        echo "Tentative de recréation de la table annonce_event...\n";
        
        // Extraire les données
        $stmt = $pdo->query("SELECT * FROM annonce_event_backup");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Supprimer la table originale
        $pdo->exec("DROP TABLE IF EXISTS annonce_event");
        
        // Recréer la table avec AUTO_INCREMENT
        $pdo->exec("CREATE TABLE annonce_event (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            departure_date DATETIME NULL,
            date_publication DATETIME NOT NULL,
            driver_id INT NOT NULL,
            car_id INT NOT NULL,
            status VARCHAR(10) NOT NULL,
            available_seats INT NOT NULL,
            date_termination DATETIME NULL,
            event_id INT NOT NULL,
            departure_point VARCHAR(255) NOT NULL,
            arrival_point VARCHAR(255) NOT NULL,
            prix DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )");
        
        // Réinsérer les données avec de nouveaux ID
        $insertStmt = $pdo->prepare("INSERT INTO annonce_event (
            id, titre, description, departure_date, date_publication, driver_id, car_id, status, 
            available_seats, date_termination, event_id, departure_point, arrival_point, prix
        ) VALUES (
            :id, :titre, :description, :departure_date, :date_publication, :driver_id, :car_id, :status, 
            :available_seats, :date_termination, :event_id, :departure_point, :arrival_point, :prix
        )");
        
        foreach ($data as $row) {
            // Ajouter 50 à chaque ID pour éviter les conflits
            $row['id'] = intval($row['id']) + 50;
            $insertStmt->execute($row);
        }
        
        // Définir l'auto-increment à une valeur sûre
        $pdo->exec("ALTER TABLE annonce_event AUTO_INCREMENT = 100");
        echo "Table annonce_event recréée avec succès.\n";
    }
    
    // 2. Réparer la table car
    echo "Réparation de la table car...\n";
    try {
        $pdo->exec("ALTER TABLE car MODIFY id INT AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE car AUTO_INCREMENT = 50");
        echo "Table car corrigée.\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de car: " . $e->getMessage() . "\n";
    }
    
    // 3. Réparer la table annonce
    echo "Réparation de la table annonce...\n";
    try {
        $pdo->exec("ALTER TABLE annonce MODIFY id INT AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE annonce AUTO_INCREMENT = 50");
        echo "Table annonce corrigée.\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de annonce: " . $e->getMessage() . "\n";
    }
    
    // 4. Réparer la table trajet
    echo "Réparation de la table trajet...\n";
    try {
        $pdo->exec("ALTER TABLE trajet MODIFY id INT AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE trajet AUTO_INCREMENT = 50");
        echo "Table trajet corrigée.\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de trajet: " . $e->getMessage() . "\n";
    }
    
    // 5. Réparer la table event
    echo "Réparation de la table event...\n";
    try {
        $pdo->exec("ALTER TABLE event MODIFY id_event INT AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE event AUTO_INCREMENT = 50");
        echo "Table event corrigée.\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de event: " . $e->getMessage() . "\n";
    }
    
    // Réactiver les contraintes de clé étrangère
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Opération terminée. Les tables ont été corrigées.\n";
    
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données: " . $e->getMessage();
} 