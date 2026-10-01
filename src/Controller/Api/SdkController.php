<?php

namespace App\Controller\Api;

use App\Repository\ApplicationRepository;
use App\Service\DailyCodeAutomationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SdkController extends AbstractController
{
    public function __construct(
        private ApplicationRepository $applicationRepo,
        private DailyCodeAutomationService $automationService
    ) {}

    /**
     * Endpoint public de vérification d'un code quotidien par l'application testée
     */
    #[Route('/api/sdk/verify-day', name: 'api_sdk_verify_day', methods: ['POST'])]
    public function verifyDay(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Récupérer apiKey depuis le header ou le corps de la requête
        $apiKey = $request->headers->get('X-App-Key') 
            ?: ($request->headers->get('X-API-KEY') ?: ($data['apiKey'] ?? ''));

        $panelisteId = $data['panelisteId'] ?? ($data['panelisteUid'] ?? ($data['testerId'] ?? ''));
        $code = $data['code'] ?? ($data['reference'] ?? ($data['codeDuJour'] ?? ''));

        $result = $this->automationService->verifySdkCode($apiKey, $panelisteId, $code);

        $statusCode = $result['success'] ? 200 : ($result['code'] ?? 400);

        return $this->json($result, $statusCode);
    }

    /**
     * Endpoint public permettant au SDK de tester la connectivité et récupérer la config
     */
    #[Route('/api/sdk/app-info', name: 'api_sdk_app_info', methods: ['GET'])]
    public function appInfo(Request $request): JsonResponse
    {
        $apiKey = $request->headers->get('X-App-Key') 
            ?: ($request->query->get('apiKey') ?: '');

        if (empty($apiKey)) {
            return $this->json(['error' => 'Clé d\'intégration X-App-Key requise'], 401);
        }

        $app = $this->applicationRepo->findByApiKey($apiKey);
        if (!$app) {
            return $this->json(['error' => 'Application non reconnue'], 404);
        }

        return $this->json([
            'status' => 'connected',
            'application' => [
                'id' => $app->getId(),
                'nom' => $app->getNom(),
                'version' => $app->getVersion(),
                'plateforme' => $app->getPlateforme(),
                'statut' => $app->getStatut(),
                'dureeJours' => $app->getDureeJoursDefaut() ?: 14,
            ],
            'server' => 'Samré Central Testing Hub',
            'timestamp' => (new \DateTime())->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Endpoint public permettant au SDK de vérifier si le testeur a déjà validé sa journée aujourd'hui
     */
    #[Route('/api/sdk/status', name: 'api_sdk_status', methods: ['GET', 'POST'])]
    public function checkStatus(Request $request, \App\Repository\ParticipationRepository $participationRepo): JsonResponse
    {
        $apiKey = $request->headers->get('X-App-Key') 
            ?: ($request->headers->get('X-API-KEY') ?: ($request->query->get('apiKey') ?: ''));
        
        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($apiKey) && !empty($data['apiKey'])) {
            $apiKey = $data['apiKey'];
        }

        $panelisteId = $request->query->get('panelisteId') 
            ?: ($request->query->get('panelisteUid') ?: ($data['panelisteId'] ?? ($data['panelisteUid'] ?? '')));

        if (empty($apiKey)) {
            return $this->json(['error' => 'Clé d\'intégration requise'], 401);
        }

        $app = $this->applicationRepo->findByApiKey($apiKey);
        if (!$app) {
            return $this->json(['error' => 'Application non reconnue'], 404);
        }

        if (empty($panelisteId)) {
            return $this->json([
                'validatedToday' => false,
                'applicationActive' => $app->getStatut() === 'active',
            ]);
        }

        $participation = $participationRepo->findOneBy(['panelisteUid' => trim($panelisteId)]);
        if (!$participation) {
            return $this->json([
                'validatedToday' => false,
                'applicationActive' => $app->getStatut() === 'active',
            ]);
        }

        $todayRef = $this->automationService->hasValidatedToday($participation);
        $currentDay = $this->automationService->calculateCurrentDay($participation);

        return $this->json([
            'validatedToday' => $todayRef !== null,
            'jour' => $todayRef ? ($todayRef->getEtape()?->getJour() ?: $currentDay) : $currentDay,
            'applicationActive' => $app->getStatut() === 'active',
            'message' => $todayRef !== null 
                ? 'Votre journée a déjà été validée avec succès aujourd\'hui !' 
                : 'En attente de validation.',
        ]);
    }

    /**
     * Endpoint public pour la page développeur d'intégration (via token unique)
     */
    #[Route('/api/public/integration/{token}', name: 'api_public_integration_info', methods: ['GET'])]
    public function getIntegrationInfo(string $token): JsonResponse
    {
        $app = $this->applicationRepo->findByTokenIntegration($token);
        if (!$app) {
            return $this->json(['error' => 'Lien d\'intégration introuvable ou expiré.'], 404);
        }

        return $this->json([
            'application' => [
                'id' => $app->getId(),
                'nom' => $app->getNom(),
                'description' => $app->getDescription(),
                'plateforme' => $app->getPlateforme(),
                'version' => $app->getVersion(),
                'statut' => $app->getStatut(),
                'dureeJours' => $app->getDureeJoursDefaut() ?: 14,
                'nbMaxPanelistes' => $app->getNbMaxPanelistes() ?: 12,
            ],
            'apiKey' => $app->getApiKey(),
            'tokenIntegration' => $app->getTokenIntegration(),
            'instructions' => [
                'step1' => 'Intégrez le formulaire de test dans un écran de votre application (ex: Paramètres > Espace Testeur).',
                'step2' => 'Demandez au testeur de renseigner son Identifiant Unique Panéliste (fourni par Samré) et le Code du Jour.',
                'step3' => 'Envoyez ces données en POST sur /api/sdk/verify-day avec votre X-App-Key.',
            ],
            'endpoints' => [
                'verify' => '/api/sdk/verify-day',
                'info' => '/api/sdk/app-info',
            ]
        ]);
    }
}
