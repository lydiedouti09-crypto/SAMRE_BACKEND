<?php

namespace App\EventSubscriber;

use App\Service\RateLimiterService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitSubscriber implements EventSubscriberInterface
{
    // Configuration des règles de limitation de débit
    private const RATE_LIMITS = [
        'api_login' => [
            'path' => '/api/login',
            'methods' => ['POST'],
            'max_attempts' => 5,      // 5 tentatives de connexion max
            'decay_seconds' => 60,     // par minute
            'error_message' => 'Trop de tentatives de connexion échouées. Veuillez patienter avant de réessayer.',
        ],
        'api_forgot_password' => [
            'path' => '/api/forgot-password',
            'methods' => ['POST'],
            'max_attempts' => 3,      // 3 demandes de réinitialisation max
            'decay_seconds' => 600,   // par tranche de 10 minutes
            'error_message' => 'Trop de demandes de réinitialisation de mot de passe. Veuillez patienter 10 minutes.',
        ],
        'api_register' => [
            'path' => '/api/register',
            'methods' => ['POST'],
            'max_attempts' => 5,      // 5 inscriptions max
            'decay_seconds' => 3600,  // par heure
            'error_message' => 'Limite de création de compte atteinte depuis cette adresse IP. Réessayez plus tard.',
        ],
    ];

    public function __construct(
        private RateLimiterService $rateLimiter
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 20 pour s'exécuter avant le pare-feu d'authentification Symfony (priorité 8)
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = rtrim($request->getPathInfo(), '/');
        $method = strtoupper($request->getMethod());

        foreach (self::RATE_LIMITS as $actionName => $config) {
            $configuredPath = rtrim($config['path'], '/');

            if ($pathInfo === $configuredPath && in_array($method, $config['methods'], true)) {
                $clientIp = $request->getClientIp() ?: 'unknown_client';

                $result = $this->rateLimiter->check(
                    $actionName,
                    $clientIp,
                    $config['max_attempts'],
                    $config['decay_seconds']
                );

                if (!$result['allowed']) {
                    $response = new JsonResponse([
                        'success' => false,
                        'error' => $config['error_message'],
                        'retry_after' => $result['retry_after'],
                    ], Response::HTTP_TOO_MANY_REQUESTS);

                    $response->headers->set('Retry-After', (string) $result['retry_after']);
                    $response->headers->set('X-RateLimit-Limit', (string) $result['limit']);
                    $response->headers->set('X-RateLimit-Remaining', '0');
                    $response->headers->set('X-RateLimit-Reset', (string) (time() + $result['retry_after']));

                    $event->setResponse($response);
                    return;
                }
            }
        }
    }
}
