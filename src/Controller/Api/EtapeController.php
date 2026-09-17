<?php

namespace App\Controller\Api;

use App\Entity\Etape;
use App\Repository\EtapeRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/etapes', name: 'api_etapes_')]
class EtapeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EtapeRepository $repo,
        private MissionRepository $missionRepo,
        private \App\Service\DailyCodeAutomationService $automationService
    ) {}

    #[Route('/mission/{missionId}', name: 'by_mission', methods: ['GET'])]
    public function byMission(int $missionId): JsonResponse
    {
        $mission = $this->missionRepo->find($missionId);
        if ($mission) {
            $this->automationService->ensureAllDailyEtapesForMission($mission);
        }
        $etapes = $this->repo->findBy(['mission' => $missionId], ['ordre' => 'ASC']);
        return $this->json($etapes, 200, [], ['groups' => 'etape:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $mission = $this->missionRepo->find($data['missionId'] ?? 0);
        if (!$mission) return $this->json(['error' => 'Mission not found'], 404);

        $etape = new Etape();
        $etape->setMission($mission);
        $etape->setTitre($data['titre']);
        $etape->setDescription($data['description'] ?? '');
        $etape->setInstructions($data['instructions'] ?? '');
        $etape->setOrdre($data['ordre'] ?? count($mission->getEtapes()) + 1);
        $etape->setJour($data['jour'] ?? 0);
        $etape->setResultatAttendu($data['resultatAttendu'] ?? '');
        $etape->setBesoinReference($data['besoinReference'] ?? false);
        $etape->setDureeEstimee($data['dureeEstimee'] ?? '');
        $etape->setStatut('non_commence');
        $etape->setDateCreation(new \DateTime());

        $this->em->persist($etape);
        $this->em->flush();

        return $this->json($etape, 201, [], ['groups' => 'etape:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Etape $etape, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['titre'])) $etape->setTitre($data['titre']);
        if (isset($data['description'])) $etape->setDescription($data['description']);
        if (isset($data['instructions'])) $etape->setInstructions($data['instructions']);
        if (isset($data['ordre'])) $etape->setOrdre($data['ordre']);
        if (isset($data['statut'])) $etape->setStatut($data['statut']);

        $this->em->flush();
        return $this->json($etape, 200, [], ['groups' => 'etape:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Etape $etape): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $this->em->remove($etape);
        $this->em->flush();
        return $this->json(null, 204);
    }
}