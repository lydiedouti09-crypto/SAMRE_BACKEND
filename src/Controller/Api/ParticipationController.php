<?php

namespace App\Controller\Api;

use App\Entity\Participation;
use App\Repository\MissionRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/participations', name: 'api_participations_')]
class ParticipationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParticipationRepository $repo,
        private MissionRepository $missionRepo,
        private \App\Service\DailyCodeAutomationService $automationService
    ) {}

    #[Route('', name: 'my', methods: ['GET'])]
    public function myParticipations(): JsonResponse
    {
        $user = $this->getUser();
        $participations = $this->repo->findBy(['User' => $user]);
        return $this->json($participations, 200, [], ['groups' => 'participation:read']);
    }

    #[Route('/mission/{missionId}', name: 'join', methods: ['POST'])]
    public function join(int $missionId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $mission = $this->missionRepo->find($missionId);

        if (!$mission) {
            return $this->json(['error' => 'Mission not found'], 404);
        }

        $existing = $this->repo->findOneBy([
            'User' => $user,
            'mission' => $mission
        ]);
        if ($existing) {
            return $this->json(['error' => 'Already participating'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $contratAccepte = $data['contratAccepte'] ?? false;

        $participation = new Participation();
        $participation->setUser($user);
        $participation->setMission($mission);
        // Candidature soumise : statut initial 'en_attente' soumis à acceptation de l'administrateur
        $participation->setStatus('en_attente');
        $participation->setContratAccepte($contratAccepte);
        $participation->setDateAcceptation($contratAccepte ? new \DateTime() : null);
        $participation->setProgression(0);
        $participation->setEtapesCompletees(0);
        $participation->setEtapesTotal(count($mission->getEtapes()));
        $participation->setDateCreation(new \DateTime());

        $this->em->persist($participation);
        $this->em->flush();

        return $this->json($participation, 201, [], ['groups' => 'participation:read']);
    }

    #[Route('/{id}/start', name: 'start', methods: ['POST'])]
    public function start(Participation $participation): JsonResponse
    {
        if ($participation->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        // Seule une candidature validée par l'administrateur peut démarrer le test
        if (!in_array($participation->getStatus(), ['acceptee', 'en_cours'])) {
            return $this->json([
                'error' => 'Votre candidature est en attente de validation par l\'administrateur. Vous ne pouvez pas encore débuter le test.'
            ], 403);
        }

        $participation->setStatus('en_cours');
        if (!$participation->getDateDebut()) {
            $participation->setDateDebut(new \DateTime());
        }
        $this->automationService->ensureDailyCodeForParticipation($participation);
        $this->em->flush();

        return $this->json($participation, 200, [], ['groups' => 'participation:read']);
    }
}