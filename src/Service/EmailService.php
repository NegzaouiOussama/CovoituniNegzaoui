<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use App\Entity\Reclamation;

class EmailService
{
    private $utilisateurRepository;
    private $senderEmail;
    private $senderName;
    private $emailPassword;

    public function __construct(
        UtilisateurRepository $utilisateurRepository
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->senderEmail = 'covoituni.tn@gmail.com';
        $this->senderName = 'Covoituni';
        $this->emailPassword = 'lkym bkdy zolc nodr';
    }

    /**
     * Send reservation confirmation email to passenger
     */
    public function sendReservationConfirmationEmail(Reservation $reservation): bool
    {
        try {
            // Get passenger user
            $passengerId = $reservation->getUserId();
            error_log("[EMAIL DEBUG] Recherche du passager avec ID: $passengerId");
            
            $passenger = $this->utilisateurRepository->find($passengerId);
            
            if (!$passenger) {
                error_log("[EMAIL ERROR] Aucun passager trouvé avec l'ID: $passengerId");
                return false;
            }
            
            $passengerEmail = $passenger->getEmail();
            if (!$passengerEmail) {
                error_log("[EMAIL ERROR] Le passager n'a pas d'email: " . $passenger->getUsername());
                return false;
            }
            
            error_log("[EMAIL DEBUG] Préparation de l'email pour: $passengerEmail");

            $subject = 'Confirmation de votre réservation - Covoituni';
            $messageBody = $this->getReservationConfirmationContent($reservation, $passenger);

            // Utilisation de PHPMailer au lieu de Symfony Mailer
            $mail = new PHPMailer(true);
            
            try {
                // Configuration du serveur
                $mail->SMTPDebug = SMTP::DEBUG_OFF;                     // Désactiver le débogage verbeux
                $mail->isSMTP();                                           // Envoi via SMTP
                $mail->Host       = 'smtp.gmail.com';                      // Serveur SMTP Gmail
                $mail->SMTPAuth   = true;                                  // Activer l'authentification SMTP
                $mail->Username   = $this->senderEmail;                    // Adresse Gmail SMTP
                $mail->Password   = $this->emailPassword;                  // Mot de passe Gmail (app password)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           // Activer le chiffrement TLS implicite
                $mail->Port       = 465;                                   // Port TCP pour le chiffrement TLS implicite; 587 pour TLS
                $mail->CharSet    = 'UTF-8';                              // Encodage UTF-8

                // Destinataires
                $mail->setFrom($this->senderEmail, $this->senderName);
                $mail->addAddress($passengerEmail);                        // Ajouter un destinataire

                // Contenu
                $mail->isHTML(true);                                       // Format HTML
                $mail->Subject = $subject;
                $mail->Body    = $messageBody;
                // Version texte simple (facultatif)
                $mail->AltBody = strip_tags(str_replace(['<div>', '</div>', '<p>', '</p>'], ["\n", '', "\n", ''], $messageBody));

                error_log("[EMAIL DEBUG] Tentative d'envoi avec PHPMailer");
                $mail->send();
                error_log("[EMAIL SUCCESS] Email envoyé avec succès à: $passengerEmail");
                return true;
            } catch (Exception $e) {
                error_log("[EMAIL ERROR] PHPMailer n'a pas pu envoyer le message. Erreur: {$mail->ErrorInfo}");
                // Pour le débogage en développement
                return false;
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $errorCode = $e->getCode();
            $trace = $e->getTraceAsString();
            error_log("[EMAIL ERROR] Erreur générale: Code=$errorCode Message=$errorMsg");
            error_log("[EMAIL ERROR] Trace: " . substr($trace, 0, 1000)); // Limiter la taille de la trace
            return false;
        }
    }

    /**
     * Format the content of the confirmation email
     */
    private function getReservationConfirmationContent(Reservation $reservation, Utilisateur $passenger): string
    {
        $annonceType = $reservation->getType();
        error_log("[EMAIL DEBUG] Type de réservation: $annonceType");
        
        if ($annonceType === 'TRAJET') {
            $annonce = $reservation->getAnnonce();
            
            if (!$annonce) {
                error_log("[EMAIL ERROR] Aucune annonce trouvée pour la réservation de trajet");
                return $this->getDefaultConfirmationContent($passenger);
            }
            
            $trajet = $annonce->getTrajet();
            $departurePoint = $trajet ? $trajet->getDeparturePoint() : 'Non spécifié';
            $arrivalPoint = $trajet ? $trajet->getArrivalPoint() : 'Non spécifié';
            $departureDate = $annonce->getDepartureDate() ? $annonce->getDepartureDate()->format('d/m/Y H:i') : 'Non spécifiée';
            
            error_log("[EMAIL DEBUG] Informations de trajet: $departurePoint -> $arrivalPoint le $departureDate");
            return $this->getTrajetConfirmationContent($passenger, $departurePoint, $arrivalPoint, $departureDate);
        } elseif ($annonceType === 'EVENT') {
            $annonceEvent = $reservation->getAnnonceEvent();
            
            if (!$annonceEvent) {
                error_log("[EMAIL ERROR] Aucune annonce d'événement trouvée");
                return $this->getDefaultConfirmationContent($passenger);
            }
            
            $event = $annonceEvent->getEvent();
            $eventName = $event ? $event->getNom() : 'Événement';
            $eventDate = $event && $event->getDateEvent() ? $event->getDateEvent()->format('d/m/Y') : 'Non spécifiée';
            $eventTime = $event && $event->getHeureEvent() ? $event->getHeureEvent()->format('H:i') : '';
            $eventDateTime = $eventDate . ($eventTime ? ' à ' . $eventTime : '');
            $eventLocation = $event ? $event->getLieu() : 'Non spécifiée';
            
            error_log("[EMAIL DEBUG] Informations d'événement: $eventName à $eventLocation le $eventDateTime");
            return $this->getEventConfirmationContent($passenger, $eventName, $eventDateTime, $eventLocation);
        }
        
        error_log("[EMAIL DEBUG] Type de réservation non reconnu, utilisation du template par défaut");
        return $this->getDefaultConfirmationContent($passenger);
    }
    
    /**
     * Get default confirmation email content
     */
    private function getDefaultConfirmationContent(Utilisateur $passenger): string
    {
        $firstName = $passenger->getPrenom();
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Réservation Confirmée</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour $firstName,</p>
                        <p>Votre réservation a été acceptée par le conducteur.</p>
                        <p>Merci d'utiliser Covoituni pour vos déplacements!</p>
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                        <p>Pour toute question, contactez-nous à covoituni.tn@gmail.com</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Get trajet confirmation email content
     */
    private function getTrajetConfirmationContent(Utilisateur $passenger, string $departurePoint, string $arrivalPoint, string $departureDate): string
    {
        $firstName = $passenger->getPrenom();
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .details { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Réservation de Trajet Confirmée</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour $firstName,</p>
                        <p>Bonne nouvelle! Votre réservation de trajet a été acceptée par le conducteur.</p>
                        
                        <div class='details'>
                            <p><strong>Lieu de départ:</strong> $departurePoint</p>
                            <p><strong>Destination:</strong> $arrivalPoint</p>
                            <p><strong>Date et heure de départ:</strong> $departureDate</p>
                        </div>
                        
                        <p>N'oubliez pas d'être ponctuel au point de rendez-vous!</p>
                        <p>Merci d'utiliser Covoituni pour vos déplacements!</p>
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                        <p>Pour toute question, contactez-nous à covoituni.tn@gmail.com</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Get event confirmation email content
     */
    private function getEventConfirmationContent(Utilisateur $passenger, string $eventName, string $eventDate, string $eventLocation): string
    {
        $firstName = $passenger->getPrenom();
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .details { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Réservation d'Événement Confirmée</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour $firstName,</p>
                        <p>Bonne nouvelle! Votre réservation pour l'événement a été acceptée par le conducteur.</p>
                        
                        <div class='details'>
                            <p><strong>Événement:</strong> $eventName</p>
                            <p><strong>Lieu:</strong> $eventLocation</p>
                            <p><strong>Date et heure:</strong> $eventDate</p>
                        </div>
                        
                        <p>Nous sommes impatients de vous voir à cet événement!</p>
                        <p>Merci d'utiliser Covoituni pour vos déplacements!</p>
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                        <p>Pour toute question, contactez-nous à covoituni.tn@gmail.com</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Send notification email when a reclamation is submitted
     */
    public function sendReclamationNotificationEmail(Reclamation $reclamation): bool
    {
        try {
            // Get user info
            $user = $reclamation->getUser();
            if (!$user) {
                error_log("[EMAIL ERROR] Aucun utilisateur trouvé pour la réclamation");
                return false;
            }
            
            $userEmail = $user->getEmail();
            $userName = $user->getPrenom() . ' ' . $user->getNom();
            $userRole = in_array('ROLE_CONDUCTEUR', $user->getRoles()) ? 'Conducteur' : 'Passager';
            
            error_log("[EMAIL DEBUG] Préparation de l'email de réclamation par: $userName ($userEmail)");

            $subject = 'Nouvelle réclamation reçue - Covoituni';
            $messageBody = $this->getReclamationNotificationContent($reclamation, $user, $userRole);

            // Use PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Server configuration
                $mail->SMTPDebug = SMTP::DEBUG_OFF;                     
                $mail->isSMTP();                                        
                $mail->Host       = 'smtp.gmail.com';                   
                $mail->SMTPAuth   = true;                               
                $mail->Username   = $this->senderEmail;                 
                $mail->Password   = $this->emailPassword;               
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        
                $mail->Port       = 465;                                
                $mail->CharSet    = 'UTF-8';                           

                // Recipients
                $mail->setFrom($this->senderEmail, $this->senderName);
                $mail->addAddress($this->senderEmail);                  // Send to admin email
                $mail->addReplyTo($userEmail, $userName);               // Reply to the user

                // Content
                $mail->isHTML(true);                                   
                $mail->Subject = $subject;
                $mail->Body    = $messageBody;
                $mail->AltBody = strip_tags(str_replace(['<div>', '</div>', '<p>', '</p>'], ["\n", '', "\n", ''], $messageBody));

                error_log("[EMAIL DEBUG] Tentative d'envoi de notification de réclamation");
                $mail->send();
                error_log("[EMAIL SUCCESS] Notification de réclamation envoyée avec succès");
                
                // Also send confirmation email to the user
                $this->sendReclamationConfirmationToUser($reclamation, $user);
                
                return true;
            } catch (Exception $e) {
                error_log("[EMAIL ERROR] PHPMailer n'a pas pu envoyer le message. Erreur: {$mail->ErrorInfo}");
                return false;
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $errorCode = $e->getCode();
            $trace = $e->getTraceAsString();
            error_log("[EMAIL ERROR] Erreur générale: Code=$errorCode Message=$errorMsg");
            error_log("[EMAIL ERROR] Trace: " . substr($trace, 0, 1000));
            return false;
        }
    }

    /**
     * Send confirmation email to user who submitted the reclamation
     */
    private function sendReclamationConfirmationToUser(Reclamation $reclamation, Utilisateur $user): bool
    {
        try {
            $userEmail = $user->getEmail();
            if (!$userEmail) {
                error_log("[EMAIL ERROR] L'utilisateur n'a pas d'email: " . $user->getUsername());
                return false;
            }
            
            $subject = 'Confirmation de votre réclamation - Covoituni';
            $messageBody = $this->getReclamationConfirmationContent($reclamation, $user);

            $mail = new PHPMailer(true);
            
            try {
                // Server configuration
                $mail->SMTPDebug = SMTP::DEBUG_OFF;                    
                $mail->isSMTP();                                       
                $mail->Host       = 'smtp.gmail.com';                  
                $mail->SMTPAuth   = true;                              
                $mail->Username   = $this->senderEmail;                
                $mail->Password   = $this->emailPassword;              
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;       
                $mail->Port       = 465;                               
                $mail->CharSet    = 'UTF-8';                           

                // Recipients
                $mail->setFrom($this->senderEmail, $this->senderName);
                $mail->addAddress($userEmail);                         

                // Content
                $mail->isHTML(true);                                  
                $mail->Subject = $subject;
                $mail->Body    = $messageBody;
                $mail->AltBody = strip_tags(str_replace(['<div>', '</div>', '<p>', '</p>'], ["\n", '', "\n", ''], $messageBody));

                $mail->send();
                error_log("[EMAIL SUCCESS] Email de confirmation de réclamation envoyé à: $userEmail");
                return true;
            } catch (Exception $e) {
                error_log("[EMAIL ERROR] PHPMailer n'a pas pu envoyer le message à l'utilisateur. Erreur: {$mail->ErrorInfo}");
                return false;
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("[EMAIL ERROR] Erreur lors de l'envoi de confirmation à l'utilisateur: $errorMsg");
            return false;
        }
    }

    /**
     * Format the content of the reclamation notification email to admin
     */
    private function getReclamationNotificationContent(Reclamation $reclamation, Utilisateur $user, string $userRole): string
    {
        $userName = $user->getPrenom() . ' ' . $user->getNom();
        $userEmail = $user->getEmail();
        $subject = $reclamation->getSubject();
        $description = $reclamation->getDescription();
        $date = $reclamation->getDate() ? $reclamation->getDate()->format('d/m/Y H:i') : 'Non spécifiée';
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .details { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Nouvelle Réclamation</h1>
                    </div>
                    <div class='content'>
                        <p>Une nouvelle réclamation a été soumise par un utilisateur.</p>
                        
                        <div class='details'>
                            <p><strong>Utilisateur:</strong> $userName ($userRole)</p>
                            <p><strong>Email:</strong> $userEmail</p>
                            <p><strong>Date:</strong> $date</p>
                            <p><strong>Sujet:</strong> $subject</p>
                            <p><strong>Description:</strong></p>
                            <p>$description</p>
                        </div>
                        
                        <p>Veuillez traiter cette réclamation dès que possible.</p>
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Format the content of the confirmation email to user
     */
    private function getReclamationConfirmationContent(Reclamation $reclamation, Utilisateur $user): string
    {
        $firstName = $user->getPrenom();
        $subject = $reclamation->getSubject();
        $date = $reclamation->getDate() ? $reclamation->getDate()->format('d/m/Y H:i') : 'Non spécifiée';
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .details { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Réclamation Enregistrée</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour $firstName,</p>
                        <p>Nous avons bien reçu votre réclamation et nous vous remercions de nous avoir fait part de votre problème.</p>
                        
                        <div class='details'>
                            <p><strong>Sujet:</strong> $subject</p>
                            <p><strong>Date de soumission:</strong> $date</p>
                            <p><strong>Statut:</strong> En attente de traitement</p>
                        </div>
                        
                        <p>Notre équipe va examiner votre réclamation dans les plus brefs délais et vous tiendra informé de son évolution.</p>
                        <p>Vous pouvez suivre l\'état de votre réclamation dans la section \"Mes réclamations\" de votre espace personnel.</p>
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                        <p>Pour toute question, contactez-nous à covoituni.tn@gmail.com</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}
