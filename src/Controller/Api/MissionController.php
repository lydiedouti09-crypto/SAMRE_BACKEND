<?php

namespace App\Controller\Api;

use App\Entity\Mission;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/missions', name: 'api_missions_')]
class MissionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, MissionRepository $repo): JsonResponse
    {
        $statut = $request->query->get('statut');
        $plateforme = $request->query->get('plateforme');

        $criteria = [];
        if ($statut) $criteria['statut'] = $statut;
        if ($plateforme) $criteria['plateforme'] = $plateforme;

        $missions = empty($criteria) ? $repo->findAll() : $repo->findBy($criteria);

        return $this->json($missions, 200, [], ['groups' => 'mission:read']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Mission $mission): JsonResponse
    {
        return $this->json($mission, 200, [], ['groups' => 'mission:read']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $mission = new Mission();
        $mission->setTitre($data['titre']);
        $mission->setDescription($data['description']);
        $mission->setObjectif($data['objectif'] ?? '');
        $mission->setImage($data['image'] ?? '');
        $mission->setApplication($data['application']);
        $mission->setVersionApplication($data['versionApplication'] ?? null);
        $mission->setPlateforme($data['plateforme']);
        $mission->setLienApplication($data['lienApplication'] ?? '');
        $mission->setDateDebut(new \DateTime($data['dateDebut']));
        $mission->setDateFin(new \DateTime($data['dateFin']));
        $mission->setDureeEstimee($data['dureeEstimee'] ?? '');
        $mission->setRemuneration($data['remuneration']);
        $mission->setConditions($data['conditions'] ?? '');
        $mission->setConditionsParticipation($data['conditionsParticipation'] ?? '');
        $mission->setNombreParticipantsSouhaites($data['nombreParticipantsSouhaites'] ?? 0);
        $mission->setNombreParticipantsActuels(0);
        $mission->setStatut('brouillon');
        $mission->setDateCreation(new \DateTime());
        $mission->setResponsable($user);

        $this->em->persist($mission);
        $this->em->flush();

        return $this->json($mission, 201, [], ['groups' => 'mission:read']);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Mission $mission, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['titre'])) $mission->setTitre($data['titre']);
        if (isset($data['description'])) $mission->setDescription($data['description']);
        if (isset($data['statut'])) $mission->setStatut($data['statut']);
        if (isset($data['objectif'])) $mission->setObjectif($data['objectif']);
        if (isset($data['remuneration'])) $mission->setRemuneration($data['remuneration']);
        if (isset($data['conditions'])) $mission->setConditions($data['conditions']);
        if (isset($data['nombreParticipantsSouhaites'])) $mission->setNombreParticipantsSouhaites($data['nombreParticipantsSouhaites']);

        $this->em->flush();
        return $this->json($mission, 200, [], ['groups' => 'mission:read']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Mission $mission): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Admin only'], 403);
        }

        $this->em->remove($mission);
        $this->em->flush();
        return $this->json(null, 204);
    }
}