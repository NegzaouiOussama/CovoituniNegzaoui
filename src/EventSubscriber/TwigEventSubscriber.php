<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $security;

    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->security = $security;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Ne s'applique pas aux sous-requêtes
        if (!$event->isMainRequest()) {
            return;
        }

        // Ajoute l'utilisateur actuel à tous les templates Twig
        if (null !== $this->security->getUser()) {
            $this->twig->addGlobal('user', $this->security->getUser());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
} 