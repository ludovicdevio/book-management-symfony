<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Psr\Log\LoggerInterface;

/**
 * Event Subscriber pour la sécurité
 *
 * Design Pattern : Event Subscriber
 * S'abonne à plusieurs événements à la fois
 */
class SecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    /**
     * Appelé lors d'une connexion réussie
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        $this->logger->info('User logged in successfully', [
            'username' => $user->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);

        // Actions possibles :
        // - Mise à jour de la date de dernière connexion
        // - Enregistrement dans l'historique
        // - Envoi d'une notification
    }

    /**
     * Appelé lors d'une tentative de connexion échouée
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->logger->warning('Login attempt failed', [
            'exception' => $event->getException()->getMessage(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);

        // Actions possibles :
        // - Compteur de tentatives échouées
        // - Blocage temporaire après X tentatives
        // - Notification de sécurité
    }
}
