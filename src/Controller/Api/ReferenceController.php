<?php

namespace App\Controller\Api;

use App\Entity\Reference;
use App\Repository\EtapeRepository;
use App\Repository\ParticipationRepository;
use App\Repository\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/references', name: 'api_references_')]
class ReferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReferenceRepository $repo,
        private ParticipationRepository $participationRepo,
        private EtapeRepository $etapeRepo,
        private \App\Service\DailyCodeAutomationService $automationService
    ) {}

    #[Route('/etape/{etapeId}', name: 'for_etape', methods: ['GET'])]
    public function getForEtape(int $etapeId): JsonResponse
    {
        $user = $this->getUser();
        $etape = $this->etapeRepo->find($etapeId);
        if (!$etape) return $this->json(['error' => 'Etape not found'], 404);

        $participation = $this->participationRepo->findOneBy([
            'User' => $user,
            'mission' => $etape->getMission()
        ]);
        if (!$participation) return $this->json(['error' => 'No participation'], 404);

        $ref = $this->repo->findOneBy([
            'etape' => $etape,
            'participation' => $participation
        ]);

        if (!$ref) {
            // Unification : création d'un code standardisé via le service central d'automatisation
            $ref = $this->automationService->createReferenceForEtape($participation, $etape);
        }

        if (!$participation->getPanelisteUid()) {
            $participation->setPanelisteUid('TST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
            $this->em->flush();
        }

        return $this->json([
            'id' => $ref->getId(),
            'reference' => $ref->getReference(),
            'panelisteUid' => $participation->getPanelisteUid(),
            'application' => $etape->getMission()?->getApplication(),
            'statut' => $ref->getStatut(),
            'dateGeneration' => $ref->getDateGeneration()?->format(\DateTimeInterface::ATOM),
            'dateExpiration' => $ref->getDateExpiration()?->format(\DateTimeInterface::ATOM),
            'dateValidation' => $ref->getDateValidation()?->format(\DateTimeInterface::ATOM),
            'referenceSaisie' => $ref->getReferenceSaisie(),
            'etapeId' => $etape->getId(),
            'missionId' => $etape->getMission()?->getId(),
        ], 200);
    }

    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $etapeId = $data['etapeId'] ?? null;
        $referenceSaisie = $data['reference'] ?? '';

        $etape = $this->etapeRepo->find($etapeId);
        if (!$etape) return $this->json(['error' => 'Etape not found'], 404);

        $participation = $this->participationRepo->findOneBy([
            'User' => $user,
            'mission' => $etape->getMission()
        ]);
        if (!$participation) return $this->json(['error' => 'No participation'], 404);

        $ref = $this->repo->findOneBy([
            'etape' => $etape,
            'participation' => $participation
        ]);
        if (!$ref) return $this->json(['error' => 'No reference generated'], 404);

        $isValid = strtoupper(trim($referenceSaisie)) === $ref->getReference();

        $ref->setReferenceSaisie($referenceSaisie);
        $ref->setStatut($isValid ? 'validee' : 'incorrecte');
        if ($isValid) $ref->setDateValidation(new \DateTime());

        $etape->setStatut($isValid ? 'validee' : 'non_validee');

        if ($isValid) {
            $completed = $this->etapeRepo->count([
                'mission' => $etape->getMission(),
                'statut' => 'validee'
            ]);
            $total = count($etape->getMission()->getEtapes());
            $participation->setEtapesCompletees($completed);
            $participation->setProgression($total > 0 ? round(($completed / $total) * 100) : 0);

            if ($completed === $total) {
                $participation->setStatut('terminee');
                $participation->setDateFin(new \DateTime());
            }
        }

        $this->em->flush();

        return $this->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Référence validée ✓' : 'Référence incorrecte ✗',
            'progression' => $participation->getProgression(),
            'participation' => $participation
        ], 200, [], ['groups' => ['participation:read']]);
    }

    #[Route('/daily-code', name: 'daily_code', methods: ['GET'])]
    public function getDailyCode(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // 1. Rechercher si l'utilisateur a une participation active en cours
        $activeParticipation = $this->participationRepo->createQueryBuilder('p')
            ->where('p.User = :user')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['en_cours', 'commencee', 'acceptee'])
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $today = new \DateTime();
        $todayStr = $today->format('d/m/Y');
        $dateKey = $today->format('Y-m-d');

        if ($activeParticipation) {
            $ref = $this->automationService->ensureDailyCodeForParticipation($activeParticipation);
            $etape = $ref->getEtape();
            $jour = $etape ? ($etape->getJour() ?: $this->automationService->calculateCurrentDay($activeParticipation)) : 1;
            $mission = $activeParticipation->getMission();

            return $this->json([
                'hasActiveMission' => true,
                'code' => $ref->getReference(),
                'missionTitre' => $mission?->getTitre() ?: 'Mission active',
                'application' => $mission?->getApplication() ?: 'Application',
                'jour' => $jour,
                'statut' => $ref->getStatut(),
                'date' => $todayStr,
                'dateKey' => $dateKey,
                'panelisteUid' => $activeParticipation->getPanelisteUid(),
            ]);
        }

        // 2. Si aucune participation active, générer le code quotidien du testeur basé sur son ID et la date du jour (change chaque jour)
        $dailySeed = sprintf('%d-%s-SAMRE-DAILY', $user->getId(), $dateKey);
        $dailyHash = strtoupper(substr(md5($dailySeed), 0, 6));
        $userCode = sprintf('SAM-%s', $dailyHash);

        return $this->json([
            'hasActiveMission' => false,
            'code' => $userCode,
            'missionTitre' => null,
            'application' => null,
            'jour' => (int)$today->format('d'),
            'statut' => 'actif',
            'date' => $todayStr,
            'dateKey' => $dateKey,
            'panelisteUid' => 'TST-' . strtoupper(substr(md5((string)$user->getId()), 0, 6)),
        ]);
    }

    private function generateReferenceCode(string $prefix): string
    {
        $short = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $prefix), 0, 2));
        return $short . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}