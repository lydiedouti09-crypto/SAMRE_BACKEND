<?php

namespace App\Controller\Api;

use App\Entity\Participation;
use App\Repository\ApplicationRepository;
use App\Repository\ParticipationRepository;
use App\Service\DailyCodeService;
use App\Service\SdkSecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class DailyCodeController extends AbstractController
{
    public function __construct(
        private DailyCodeService $dailyCodeService,
        private ParticipationRepository $participationRepository,
        private ApplicationRepository $applicationRepository,
        private EntityManagerInterface $entityManager,
        private SdkSecurityService $sdkSecurityService,
    ) {
    }

    #[Route('/missions/{missionId}/daily-code', name: 'api_v1_daily_code_show', methods: ['GET'])]
    public function show(int $missionId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Utilisateur non authentifié.',
                'errors' => ['auth' => ['Non authentifié']],
            ], 401);
        }

        $participation = $this->participationRepository->findOneBy([
            'mission' => $missionId,
            'User' => $user,
        ]);

        if (!$participation || !$participation->getMission()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Participation introuvable pour cette mission.',
                'errors' => ['mission' => ['Participation introuvable']],
            ], 404);
        }

        $mission = $participation->getMission();
        $application = $mission->getApplicationEntity();
        if (!$application || !$application->getSecretKey()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Secret de l’application manquant.',
                'errors' => ['application' => ['Secret non défini']],
            ], 500);
        }

        $date = new \DateTimeImmutable('today');
        $code = $this->dailyCodeService->generateToken(
            $application->getSecretKey(),
            $mission->getId(),
            $application->getId(),
            $user->getId(),
            $date,
        );

        return $this->json([
            'success' => true,
            'data' => [
                'code' => $code,
                'missionId' => $mission->getId(),
                'applicationId' => $application->getId(),
                'testerId' => $user->getId(),
                'jour' => 1,
            ],
            'message' => 'Code du jour généré.',
            'errors' => [],
        ]);
    }

    #[Route('/sdk/verify-code', name: 'api_v1_sdk_verify_code', methods: ['POST'])]
    public function verifyCodeFromSdk(): JsonResponse
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $payload = json_decode((string) $request->getContent(), true) ?? [];

        $apiKey = $request->headers->get('X-App-Key') ?: ($payload['apiKey'] ?? '');
        $sdkToken = $request->headers->get('X-SDK-Token') ?: ($payload['sdkToken'] ?? '');
        $appId = (int) ($payload['app_id'] ?? 0);
        $testerId = (int) ($payload['tester_id'] ?? ($payload['testerId'] ?? 0));
        $submittedCode = (string) ($payload['code'] ?? '');
        $deviceId = (string) ($payload['deviceId'] ?? $request->headers->get('X-Device-Id', 'unknown'));
        $ipAddress = (string) $request->getClientIp();

        if (empty($apiKey) && $appId <= 0) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'La clé d’application ou l’identifiant de l’application est requis.',
                'errors' => ['app' => ['Application non reconnue']],
            ], 400);
        }

        if (empty($sdkToken)) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Le token SDK est requis.',
                'errors' => ['sdk' => ['Token SDK manquant']],
            ], 401);
        }

        $application = !empty($apiKey)
            ? $this->applicationRepository->findByApiKey($apiKey)
            : $this->applicationRepository->find($appId);

        if (!$application || !$application->getSecretKey()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Application SDK non reconnue.',
                'errors' => ['app' => ['Application non reconnue']],
            ], 401);
        }

        $authApplication = $this->sdkSecurityService->authenticateSdkRequest($application->getApiKey(), $sdkToken);
        if (!$authApplication) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Token SDK invalide.',
                'errors' => ['sdk' => ['Token SDK invalide']],
            ], 401);
        }

        if ($testerId <= 0 || empty($submittedCode)) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Identifiant testeur et code requis.',
                'errors' => ['code' => ['Données incomplètes']],
            ], 400);
        }

        $participation = $this->participationRepository->findOneBy([
            'User' => $testerId,
        ]);

        if (!$participation || !$participation->getMission()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Aucune participation active trouvée pour ce testeur.',
                'errors' => ['tester' => ['Participation introuvable']],
            ], 404);
        }

        $mission = $participation->getMission();
        $secretKey = $application->getSecretKey();
        $date = new \DateTimeImmutable('today');
        $expectedCode = $this->dailyCodeService->generateToken(
            $secretKey,
            $mission->getId(),
            $application->getId(),
            $testerId,
            $date,
        );

        $valid = hash_equals(strtoupper($expectedCode), strtoupper(trim($submittedCode)));

        if (!$valid) {
            return $this->json([
                'success' => false,
                'data' => ['valide' => false],
                'message' => 'Code invalide.',
                'errors' => ['code' => ['Code incorrect']],
            ], 422);
        }

        if ($this->sdkSecurityService->isReplayAttempt($participation->getId(), $deviceId, $ipAddress)) {
            return $this->json([
                'success' => false,
                'data' => ['valide' => false, 'replay' => true],
                'message' => 'Validation déjà enregistrée pour ce device ou cette IP.',
                'errors' => ['security' => ['Tentative de replay détectée']],
            ], 409);
        }

        $jours = $participation->getJoursValides() ?? [];
        $jour = 1;
        if (!in_array($jour, $jours, true)) {
            $jours[] = $jour;
            $participation->setJoursValides($jours);
        }

        $this->sdkSecurityService->recordValidationAttempt($participation->getId(), $deviceId, $ipAddress, $jour);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'valide' => true,
                'jourValide' => $jour,
            ],
            'message' => 'Code validé.',
            'errors' => [],
        ]);
    }

    #[Route('/sdk/screen-location', name: 'api_v1_sdk_screen_location', methods: ['GET'])]
    public function screenLocation(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'screen' => 'daily-code-screen',
                'route' => '/daily-code',
            ],
            'message' => 'Emplacement écran retourné.',
            'errors' => [],
        ]);
    }
}
