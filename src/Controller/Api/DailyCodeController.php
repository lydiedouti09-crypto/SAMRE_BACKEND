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
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * Retourne le code du jour pour le testeur connecté sur la mission donnée.
     */
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
        if (!$application) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Application associée à la mission introuvable.',
                'errors' => ['application' => ['Application non trouvée']],
            ], 404);
        }

        // Auto-génération de la clé secrète HMAC si absente
        if (!$application->getSecretKey()) {
            $application->regenerateSecretKey();
            $this->entityManager->flush();
        }

        // Assurer que le panéliste a un identifiant unique lisible
        if (!$participation->getPanelisteUid()) {
            $participation->setPanelisteUid('TST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
            $this->entityManager->flush();
        }

        $maxDays = $application->getDureeJoursDefaut() ?: 12;
        $completedDays = (int) $participation->getEtapesCompletees();
        $currentDay = min($maxDays, $completedDays + 1);

        $code = $this->dailyCodeService->generateToken(
            $application->getSecretKey(),
            $mission->getId(),
            $application->getId(),
            $user->getId(),
            $currentDay
        );

        return $this->json([
            'success' => true,
            'data' => [
                'code' => $code,
                'missionId' => $mission->getId(),
                'applicationId' => $application->getId(),
                'applicationNom' => $application->getNom(),
                'testerId' => $user->getId(),
                'panelisteUid' => $participation->getPanelisteUid(),
                'jour' => $currentDay,
                'totalJours' => $maxDays,
                'progression' => $participation->getProgression() ?? 0,
            ],
            'message' => 'Code du jour généré.',
            'errors' => [],
        ]);
    }

    /**
     * Endpoint SDK pour vérifier le code soumis par le testeur depuis l'application mobile.
     */
    #[Route('/sdk/verify-code', name: 'api_v1_sdk_verify_code', methods: ['POST'])]
    public function verifyCodeFromSdk(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?? [];

        $apiKey = $request->headers->get('X-App-Key') 
            ?: ($request->headers->get('X-API-KEY') ?: ($payload['apiKey'] ?? ''));
        $sdkToken = $request->headers->get('X-SDK-Token') ?: ($payload['sdkToken'] ?? '');
        $appId = (int) ($payload['app_id'] ?? ($payload['applicationId'] ?? 0));
        
        $panelisteUid = trim((string) ($payload['panelisteUid'] ?? ($payload['panelisteId'] ?? '')));
        $testerId = (int) ($payload['tester_id'] ?? ($payload['testerId'] ?? 0));
        
        $submittedCode = (string) ($payload['code'] ?? ($payload['reference'] ?? ''));
        $deviceId = (string) ($payload['deviceId'] ?? ($request->headers->get('X-Device-Id') ?? 'unknown'));
        $ipAddress = (string) $request->getClientIp();

        if (empty($apiKey) && $appId <= 0) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'La clé d’application ou l’identifiant de l’application est requis.',
                'errors' => ['app' => ['Application non reconnue']],
            ], 400);
        }

        $application = !empty($apiKey)
            ? $this->applicationRepository->findByApiKey($apiKey)
            : $this->applicationRepository->find($appId);

        if (!$application) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Application non reconnue.',
                'errors' => ['app' => ['Application non reconnue']],
            ], 401);
        }

        // Si un sdkToken est fourni ou configuré sur l'app, le vérifier
        if (!empty($application->getSdkToken())) {
            if (empty($sdkToken) || !$this->sdkSecurityService->authenticateSdkRequest($application->getApiKey(), $sdkToken)) {
                return $this->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Token SDK manquant ou invalide.',
                    'errors' => ['sdk' => ['Token SDK invalide']],
                ], 401);
            }
        }

        // Auto-génération de la clé secrète HMAC si absente
        if (!$application->getSecretKey()) {
            $application->regenerateSecretKey();
            $this->entityManager->flush();
        }

        if (empty($panelisteUid) && $testerId <= 0) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Identifiant panéliste ou identifiant testeur requis.',
                'errors' => ['tester' => ['Identifiant manquant']],
            ], 400);
        }

        if (empty($submittedCode)) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Code de validation requis.',
                'errors' => ['code' => ['Code manquant']],
            ], 400);
        }

        // Recherche précise de la participation
        $participation = null;
        if (!empty($panelisteUid)) {
            $participation = $this->participationRepository->findOneBy(['panelisteUid' => $panelisteUid]);
        } elseif ($testerId > 0) {
            $participations = $this->participationRepository->findBy(['User' => $testerId]);
            foreach ($participations as $p) {
                if ($p->getMission()?->getApplicationEntity()?->getId() === $application->getId()) {
                    $participation = $p;
                    break;
                }
            }
            if (!$participation && count($participations) === 1) {
                $participation = $participations[0];
            }
        }

        if (!$participation || !$participation->getMission()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Aucune participation active trouvée pour ce testeur sur cette application.',
                'errors' => ['tester' => ['Participation introuvable']],
            ], 404);
        }

        $mission = $participation->getMission();
        if ($mission->getApplicationEntity()?->getId() !== $application->getId()) {
            return $this->json([
                'success' => false,
                'data' => null,
                'message' => 'Ce panéliste n\'est pas inscrit au projet correspondant à cette application.',
                'errors' => ['mission' => ['Application incompatible']],
            ], 403);
        }

        $maxDays = $application->getDureeJoursDefaut() ?: 12;
        $completedDays = (int) $participation->getEtapesCompletees();
        $currentDay = min($maxDays, $completedDays + 1);
        $userTesterId = $participation->getUser()?->getId() ?: $testerId;

        // Vérification HMAC du code avec le numéro de jour courant
        $secretKey = $application->getSecretKey();
        $isValid = $this->dailyCodeService->verifyCode(
            $submittedCode,
            $secretKey,
            $mission->getId(),
            $application->getId(),
            $userTesterId,
            $currentDay
        );

        // Fallback rétro-compatible avec date du jour au cas où le code a été généré via date
        if (!$isValid) {
            $isValid = $this->dailyCodeService->verifyCode(
                $submittedCode,
                $secretKey,
                $mission->getId(),
                $application->getId(),
                $userTesterId,
                new \DateTimeImmutable('today')
            );
        }

        if (!$isValid) {
            return $this->json([
                'success' => false,
                'data' => ['valide' => false],
                'message' => 'Code de validation incorrect.',
                'errors' => ['code' => ['Code incorrect']],
            ], 422);
        }

        // Vérification anti-rejeu (le jour en cours a-t-il déjà été validé ?)
        if ($this->sdkSecurityService->isReplayAttempt($participation->getId(), $deviceId, $ipAddress, $currentDay)) {
            return $this->json([
                'success' => true,
                'data' => [
                    'valide' => true,
                    'alreadyValidated' => true,
                    'jourValide' => $currentDay,
                    'progression' => $participation->getProgression(),
                ],
                'message' => 'Ce jour a déjà été validé avec succès !',
                'errors' => [],
            ], 200);
        }

        // Enregistrement de la validation
        $jours = $participation->getJoursValides() ?? [];
        if (!in_array($currentDay, $jours, true)) {
            $jours[] = $currentDay;
            $participation->setJoursValides($jours);
        }

        $this->sdkSecurityService->recordValidationAttempt($participation->getId(), $deviceId, $ipAddress, $currentDay);

        // Mise à jour de la progression
        $newCompleted = count($jours);
        $progression = min(100, (int) round(($newCompleted / max(1, $maxDays)) * 100));

        $participation->setEtapesCompletees($newCompleted);
        $participation->setEtapesTotal($maxDays);
        $participation->setProgression($progression);

        if ($newCompleted >= $maxDays) {
            $participation->setStatus('terminee');
            $participation->setDateFin(new \DateTime());
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'valide' => true,
                'alreadyValidated' => false,
                'jourValide' => $currentDay,
                'progression' => $progression,
                'totalJours' => $maxDays,
                'terminee' => ($newCompleted >= $maxDays),
            ],
            'message' => 'Félicitations ! Jour ' . $currentDay . ' validé avec succès.',
            'errors' => [],
        ]);
    }

    /**
     * Endpoint retournant l'écran cible pour l'affichage dans le SDK mobile.
     */
    #[Route('/sdk/screen-location', name: 'api_v1_sdk_screen_location', methods: ['GET'])]
    public function screenLocation(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'screen' => 'daily-code-screen',
                'route' => '/daily-code',
                'component' => 'SamreVerificationScreen',
            ],
            'message' => 'Emplacement écran retourné.',
            'errors' => [],
        ]);
    }
}
