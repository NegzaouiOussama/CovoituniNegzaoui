<?php

namespace App\Service;

use App\Entity\Annonce;
use App\Repository\UtilisateurRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class AnnonceNotificationService
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
     * Envoie une notification à tous les utilisateurs lorsqu'une nouvelle annonce de trajet est créée
     */
    public function notifyUsersAboutNewAnnonce(Annonce $annonce): bool
    {
        try {
            // Récupérer tous les utilisateurs
            $users = $this->utilisateurRepository->findAll();
            
            if (empty($users)) {
                error_log("[EMAIL ERROR] Aucun utilisateur trouvé pour envoyer les notifications");
                return false;
            }
            
            $trajet = $annonce->getTrajet();
            if (!$trajet) {
                error_log("[EMAIL ERROR] Trajet non trouvé pour l'annonce");
                return false;
            }
            
            $departurePoint = $trajet->getDeparturePoint();
            $arrivalPoint = $trajet->getArrivalPoint();
            $departureDate = $annonce->getDepartureDate() ? $annonce->getDepartureDate()->format('d/m/Y H:i') : 'Non spécifiée';
            $seatCount = $annonce->getAvailableSeats();
            
            // Récupérer le conducteur
            $driverId = $annonce->getDriverId();
            $driver = $driverId ? $this->utilisateurRepository->find($driverId) : null;
            $driverName = $driver ? $driver->getPrenom() . ' ' . $driver->getNom() : 'Un conducteur';
            
            $subject = 'Nouveau Annonce disponible - Covoituni';
            
            $successCount = 0;
            
            foreach ($users as $user) {
                // Ne pas envoyer au conducteur lui-même
                if ($driver && $user->getId() === $driverId) {
                    continue;
                }
                
                $userEmail = $user->getEmail();
                if (!$userEmail) {
                    continue;
                }
                
                $messageBody = $this->getNewAnnonceNotificationContent(
                    $user, 
                    $departurePoint, 
                    $arrivalPoint, 
                    $departureDate, 
                    $driverName, 
                    $seatCount, 
                    $annonce->getTitre()
                );
                
                if ($this->sendEmail($userEmail, $subject, $messageBody)) {
                    $successCount++;
                }
            }
            
            error_log("[EMAIL SUCCESS] Notifications envoyées avec succès à $successCount utilisateurs");
            return $successCount > 0;
            
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
     * Envoi d'email générique avec PHPMailer
     */
    private function sendEmail(string $toEmail, string $subject, string $messageBody): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            // Configuration du serveur
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->senderEmail;
            $mail->Password   = $this->emailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // Destinataires
            $mail->setFrom($this->senderEmail, $this->senderName);
            $mail->addAddress($toEmail);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $messageBody;
            $mail->AltBody = strip_tags(str_replace(['<div>', '</div>', '<p>', '</p>'], ["\n", '', "\n", ''], $messageBody));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("[EMAIL ERROR] Impossible d'envoyer l'email à $toEmail: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Template pour notification de nouvelle annonce de trajet
     */
    private function getNewAnnonceNotificationContent($user, $departurePoint, $arrivalPoint, $departureDate, $driverName, $seatCount, $titre): string
    {
        $firstName = $user->getPrenom();
        
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; }
                    .details { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                    .cta-button { display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Nouveau Annonce Disponible</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour $firstName,</p>
                        <p>Un nouveau Annonce vient d'être ajouté sur Covoituni!</p>
                        
                        <div class='details'>
                            <p><strong>Titre:</strong> $titre</p>
                            <p><strong>De:</strong> $departurePoint</p>
                            <p><strong>À:</strong> $arrivalPoint</p>
                            <p><strong>Date et heure:</strong> $departureDate</p>
                            <p><strong>Conducteur:</strong> $driverName</p>
                            <p><strong>Places disponibles:</strong> $seatCount</p>
                        </div>
                        
                        <p>Ne manquez pas cette opportunité de voyage!</p>
                       
                    </div>
                    <div class='footer'>
                        <p>Covoituni - La solution de covoiturage pour étudiants</p>
                        <p>Pour toute question, contactez-nous à covoituni.tn@gmail.com</p>
                        <p><small>Pour ne plus recevoir ces notifications, connectez-vous à votre compte et modifiez vos préférences.</small></p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
} 