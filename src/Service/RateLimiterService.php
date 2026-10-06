<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class RateLimiterService
{
    public function __construct(
        private CacheItemPoolInterface $cache
    ) {}

    /**
     * Vérifie et incrémente le compteur de requêtes pour une clé donnée.
     *
     * @param string $action Nom de l'action (ex: 'login', 'register', 'forgot_password')
     * @param string $identifier Identifiant du client (ex: IP ou IP+Email)
     * @param int $maxAttempts Nombre maximal de tentatives autorisées
     * @param int $decaySeconds Durée de la fenêtre en secondes
     * 
     * @return array{allowed: bool, limit: int, remaining: int, retry_after: int}
     */
    public function check(string $action, string $identifier, int $maxAttempts, int $decaySeconds): array
    {
        $safeKey = 'rl_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $action . '_' . $identifier);
        $item = $this->cache->getItem($safeKey);

        $currentTime = time();
        $data = $item->isHit() ? $item->get() : null;

        if (!is_array($data) || !isset($data['timestamps'])) {
            $data = [
                'timestamps' => []
            ];
        }

        // Filtrer les tentatives encore dans la fenêtre glissante
        $windowStart = $currentTime - $decaySeconds;
        $validTimestamps = array_filter(
            $data['timestamps'],
            fn($ts) => $ts > $windowStart
        );

        $attemptCount = count($validTimestamps);

        if ($attemptCount >= $maxAttempts) {
            // Calcul du temps d'attente restant avant la libération de la plus ancienne tentative
            $oldestAttempt = min($validTimestamps);
            $retryAfter = max(1, ($oldestAttempt + $decaySeconds) - $currentTime);

            return [
                'allowed' => false,
                'limit' => $maxAttempts,
                'remaining' => 0,
                'retry_after' => $retryAfter,
            ];
        }

        // Ajouter la tentative actuelle
        $validTimestamps[] = $currentTime;
        $data['timestamps'] = array_values($validTimestamps);

        $item->set($data);
        $item->expiresAfter($decaySeconds + 10);
        $this->cache->save($item);

        $remaining = max(0, $maxAttempts - count($validTimestamps));

        return [
            'allowed' => true,
            'limit' => $maxAttempts,
            'remaining' => $remaining,
            'retry_after' => 0,
        ];
    }

    /**
     * Réinitialise le compteur pour un identifiant (ex: après login réussi).
     */
    public function reset(string $action, string $identifier): void
    {
        $safeKey = 'rl_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $action . '_' . $identifier);
        $this->cache->deleteItem($safeKey);
    }
}
