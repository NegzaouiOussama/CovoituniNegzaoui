<?php
// Load Composer's autoloader
require_once './vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Create an instance of PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_OFF;                     // Disable verbose debug output
    $mail->isSMTP();                                        // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                   // SMTP server
    $mail->SMTPAuth   = true;                               // Enable SMTP authentication
    $mail->Username   = 'covoituni.tn@gmail.com';           // SMTP username
    $mail->Password   = 'lkym bkdy zolc nodr';              // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;        // Enable implicit TLS encryption
    $mail->Port       = 465;                                // TCP port to connect to; 465 for SSL
    $mail->CharSet    = 'UTF-8';                            // Set email character encoding

    // Recipients
    $mail->setFrom('covoituni.tn@gmail.com', 'Covoituni');
    $mail->addAddress('mohamedbenfredj8@gmail.com');        // Add a recipient

    // Content
    $mail->isHTML(true);                                    // Set email format to HTML
    $mail->Subject = 'Test Email from Covoituni';
    $mail->Body    = '
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
            <div class="container">
                <div class="header">
                    <h1>Test Email</h1>
                </div>
                <div class="content">
                    <h2>Salut!</h2>
                    <p>Ceci est un email de test pour vérifier le fonctionnement de PHPMailer.</p>
                </div>
                <div class="footer">
                    <p>Covoituni - La solution de covoiturage pour étudiants</p>
                </div>
            </div>
        </body>
        </html>
    ';
    $mail->AltBody = 'Salut! Ceci est un email de test pour vérifier le fonctionnement de PHPMailer.';

    // Send email
    $mail->send();
    echo '<div style="font-size: 24px; color: white; background-color: #4CAF50; padding: 20px; text-align: center; border-radius: 10px;">
        <h1>✅ Email envoyé avec succès!</h1>
        <p>Un email de test a été envoyé à mohamedbenfredj8@gmail.com</p>
    </div>';
} catch (Exception $e) {
    echo '<div style="font-size: 24px; color: white; background-color: #F44336; padding: 20px; text-align: center; border-radius: 10px;">
        <h1>❌ Erreur!</h1>
        <p>L\'email n\'a pas pu être envoyé.</p>
        <p>Message d\'erreur: ' . $mail->ErrorInfo . '</p>
    </div>';
} 