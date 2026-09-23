<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Participation;
use App\Repository\ApplicationRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SdkSecurityService
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly ParticipationRepository $participationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Authentifie l'appel SDK via apiKey et sdkToken.
     */
    public function authenticateSdkRequest(string $apiKey, string $sdkToken): ?Application
    {
        if (empty($apiKey) || empty($sdkToken)) {
            return null;
        }

        $application = $this->applicationRepository->findByApiKey($apiKey);
        if (!$application) {
            return null;
        }

        $appSdkToken = $application->getSdkToken();
        if (empty($appSdkToken) || !hash_equals($appSdkToken, $sdkToken)) {
            return null;
        }

        return $application;
    }

    /**
     * Enregistre la validation effectuée pour un jour donné.
     */
    public function recordValidationAttempt(int $participationId, string $deviceId, string $ipAddress, int $jour): void
    {
        $participation = $this->participationRepository->find($participationId);
        if (!$participation) {
            return;
        }

        $participation->addValidationAttempt($deviceId, $ipAddress, $jour);
        $this->entityManager->flush();
    }

    /**
     * Vérifie si ce jour précis a déjà été validé (rejeu) pour cette participation.
     * Note : Un même deviceId et une même IP sont parfaitement légitimes pour des jours DIFFÉRENTS (J1, J2, ... J12).
     */
    public function isReplayAttempt(int $participationId, string $deviceId, string $ipAddress, int $jour): bool
    {
        $participation = $this->participationRepository->find($participationId);
        if (!$participation) {
            return false;
        }

        // 1. Vérification dans les jours validés
        $joursValides = $participation->getJoursValides() ?? [];
        if (in_array($jour, $joursValides, true)) {
            return true;
        }

        // 2. Vérification dans l'historique de validation pour ce jour spécifique
        $history = $participation->getValidationHistory() ?? [];
        foreach ($history as $entry) {
            if (is_array($entry) && (int) ($entry['jour'] ?? 0) === $jour) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte si un même deviceId tente de valider les missions de plusieurs comptes testeurs différents.
     */
    public function isMultiAccountDevice(int $currentParticipationId, int $missionId, string $deviceId): bool
    {
        if (empty($deviceId) || $deviceId === 'unknown') {
            return false;
        }

        $participations = $this->participationRepository->findBy([
            'mission' => $missionId,
        ]);

        foreach ($participations as $otherParticipation) {
            if ($otherParticipation->getId() === $currentParticipationId) {
                continue;
            }

            $history = $otherParticipation->getValidationHistory() ?? [];
            foreach ($history as $entry) {
                if (is_array($entry) && ($entry['deviceId'] ?? '') === $deviceId) {
                    return true; // Le même appareil physique a validé pour un autre compte
                }
            }
        }

        return false;
    }
}
