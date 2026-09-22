<?php

namespace App\Service;

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

    public function authenticateSdkRequest(string $apiKey, string $sdkToken): ?object
    {
        $application = $this->applicationRepository->findByApiKey($apiKey);
        if (!$application) {
            return null;
        }

        if (empty($application->getSdkToken()) || !hash_equals($application->getSdkToken(), $sdkToken)) {
            return null;
        }

        return $application;
    }

    public function recordValidationAttempt(int $participationId, string $deviceId, string $ipAddress, int $jour): void
    {
        $participation = $this->participationRepository->find($participationId);
        if (!$participation) {
            return;
        }

        $participation->addValidationAttempt($deviceId, $ipAddress, $jour);
        $this->entityManager->flush();
    }

    public function isReplayAttempt(int $participationId, string $deviceId, string $ipAddress): bool
    {
        $participation = $this->participationRepository->find($participationId);
        if (!$participation) {
            return false;
        }

        $history = $participation->getValidationHistory() ?? [];

        foreach ($history as $entry) {
            if (is_array($entry) && (($entry['deviceId'] ?? null) === $deviceId || ($entry['ipAddress'] ?? null) === $ipAddress)) {
                return true;
            }
        }

        return false;
    }
}
