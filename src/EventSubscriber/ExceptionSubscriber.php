<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Exception Subscriber - Gestion centralisée des erreurs
 *
 * Design Pattern : Exception Handler
 * Centralise la gestion des erreurs dans toute l'application
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private LoggerInterface $logger,
        private bool $debug = false
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // Ne pas intercepter en mode debug
        if ($this->debug) {
            return;
        }

        $exception = $event->getThrowable();

        // Logging
        $this->logger->error('Exception occurred', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // Personnalisation de la réponse selon le type d'erreur
        if ($exception instanceof NotFoundHttpException) {
            $response = new Response(
                $this->twig->render('errors/404.html.twig'),
                Response::HTTP_NOT_FOUND
            );
        } else {
            $response = new Response(
                $this->twig->render('errors/500.html.twig', [
                    'message' => 'Une erreur est survenue. Veuillez réessayer.',
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $event->setResponse($response);
    }
}
