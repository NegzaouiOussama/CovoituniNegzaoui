<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\FailedMessageEvent;

class EmailSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
            FailedMessageEvent::class => 'onFailedMessage',
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();
        $recipients = implode(', ', array_keys($message->getTo()));
        
        // Log des informations sur l'email
        error_log("[EMAIL] Envoi d'un message à {$recipients} avec sujet: {$message->getSubject()}");
    }

    public function onFailedMessage(FailedMessageEvent $event): void
    {
        $message = $event->getMessage();
        $error = $event->getError();
        $recipients = implode(', ', array_keys($message->getTo()));
        
        // Log détaillé en cas d'erreur
        error_log("[EMAIL ERROR] Échec d'envoi à {$recipients}: {$error->getMessage()}");
    }
} 