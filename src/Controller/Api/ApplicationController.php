<?php

namespace App\Controller\Api;

use App\Entity\Application;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\MissionRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/applications', name: 'api_admin_applications_')]
class ApplicationController extends AbstractController
{
    public function __construct(
        private ApplicationRepository $applicationRepo,
        private MissionRepository $missionRepo,
        private ParticipationRepository $participationRepo,
        private EntityManagerInterface $em
    ) {}

    private function checkAdmin(): ?JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], 403);
        }
        return null;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $apps = $this->applicationRepo->findBy([], ['id' => 'DESC']);
        $result = [];

        foreach ($apps as $app) {
            $missions = $app->getMissions();
            $panelistesCount = 0;
            $panelistesUids = [];

            foreach ($missions as $m) {
                foreach ($m->getParticipations() as $p) {
                    if ($p->getUser() && !in_array($p->getUser()->getId(), $panelistesUids)) {
                        $panelistesUids[] = $p->getUser()->getId();
                        $panelistesCount++;
                    }
                }
            }

            $result[] = [
                'id' => $app->getId(),
                'nom' => $app->getNom(),
                'description' => $app->getDescription(),
                'logo' => $app->getLogo(),
                'plateforme' => $app->getPlateforme(),
                'version' => $app->getVersion(),
                'lienTelechargement' => $app->getLienTelechargement(),
                'developpeurNom' => $app->getDeveloppeurNom(),
                'developpeurEmail' => $app->getDeveloppeurEmail(),
                'apiKey' => $app->getApiKey(),
                'tokenIntegration' => $app->getTokenIntegration(),
                'dureeJoursDefaut' => $app->getDureeJoursDefaut() ?: 12,
                'nbMaxPanelistes' => $app->getNbMaxPanelistes() ?: 12,
                'statut' => $app->getStatut(),
                'dateCreation' => $app->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $app->getDateModification()?->format('Y-m-d H:i:s'),
                'nbMissions' => count($missions),
                'nbPanelistes' => $panelistesCount,
            ];
        }

        return $this->json($result);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $data = json_decode($request->getContent(), true) ?? [];

        $nom = trim($data['nom'] ?? '');
        if (empty($nom)) {
            return $this->json(['error' => 'Le nom de l\'application est obligatoire.'], 400);
        }

        $app = new Application();
        $app->setNom($nom);
        $app->setDescription(trim($data['description'] ?? ''));
        $app->setLogo(trim($data['logo'] ?? ''));
        $app->setPlateforme(trim($data['plateforme'] ?? 'Android'));
        $app->setVersion(trim($data['version'] ?? '1.0.0'));
        $app->setLienTelechargement(trim($data['lienTelechargement'] ?? ''));
        $app->setDeveloppeurNom(trim($data['developpeurNom'] ?? ''));
        $app->setDeveloppeurEmail(trim($data['developpeurEmail'] ?? ''));
        $app->setDureeJoursDefaut((int)($data['dureeJoursDefaut'] ?? 12));
        $app->setNbMaxPanelistes((int)($data['nbMaxPanelistes'] ?? 12));
        $app->setStatut(trim($data['statut'] ?? 'en_attente_integration'));

        $this->em->persist($app);
        $this->em->flush();

        return $this->json([
            'message' => 'Application créée avec succès !',
            'id' => $app->getId(),
            'apiKey' => $app->getApiKey(),
            'tokenIntegration' => $app->getTokenIntegration(),
        ], 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $app = $this->applicationRepo->find($id);
        if (!$app) return $this->json(['error' => 'Application non trouvée'], 404);

        $missionsData = [];
        $panelistesData = [];

        foreach ($app->getMissions() as $m) {
            $missionsData[] = [
                'id' => $m->getId(),
                'titre' => $m->getTitre(),
                'statut' => $m->getStatut(),
                'duree' => $m->getDureEstime(),
                'remuneration' => $m->getRemuneration(),
                'participants' => count($m->getParticipations()),
            ];

            foreach ($m->getParticipations() as $p) {
                $u = $p->getUser();
                $panelistesData[] = [
                    'id' => $p->getId(),
                    'panelisteUid' => $p->getPanelisteUid(),
                    'nom' => $u ? ($u->getPrenom() . ' ' . $u->getNom()) : 'Anonyme',
                    'email' => $u?->getEmail(),
                    'missionId' => $m->getId(),
                    'missionTitre' => $m->getTitre(),
                    'progression' => $p->getProgression(),
                    'statut' => $p->getStatus(),
                ];
            }
        }

        return $this->json([
            'id' => $app->getId(),
            'nom' => $app->getNom(),
            'description' => $app->getDescription(),
            'logo' => $app->getLogo(),
            'plateforme' => $app->getPlateforme(),
            'version' => $app->getVersion(),
            'lienTelechargement' => $app->getLienTelechargement(),
            'developpeurNom' => $app->getDeveloppeurNom(),
            'developpeurEmail' => $app->getDeveloppeurEmail(),
            'apiKey' => $app->getApiKey(),
            'tokenIntegration' => $app->getTokenIntegration(),
            'dureeJoursDefaut' => $app->getDureeJoursDefaut() ?: 12,
            'nbMaxPanelistes' => $app->getNbMaxPanelistes() ?: 12,
            'statut' => $app->getStatut(),
            'dateCreation' => $app->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $app->getDateModification()?->format('Y-m-d H:i:s'),
            'missions' => $missionsData,
            'panelistes' => $panelistesData,
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $app = $this->applicationRepo->find($id);
        if (!$app) return $this->json(['error' => 'Application non trouvée'], 404);

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['nom'])) $app->setNom(trim($data['nom']));
        if (isset($data['description'])) $app->setDescription(trim($data['description']));
        if (isset($data['logo'])) $app->setLogo(trim($data['logo']));
        if (isset($data['plateforme'])) $app->setPlateforme(trim($data['plateforme']));
        if (isset($data['version'])) $app->setVersion(trim($data['version']));
        if (isset($data['lienTelechargement'])) $app->setLienTelechargement(trim($data['lienTelechargement']));
        if (isset($data['developpeurNom'])) $app->setDeveloppeurNom(trim($data['developpeurNom']));
        if (isset($data['developpeurEmail'])) $app->setDeveloppeurEmail(trim($data['developpeurEmail']));
        if (isset($data['dureeJoursDefaut'])) $app->setDureeJoursDefaut((int)$data['dureeJoursDefaut']);
        if (isset($data['nbMaxPanelistes'])) $app->setNbMaxPanelistes((int)$data['nbMaxPanelistes']);
        if (isset($data['statut'])) $app->setStatut(trim($data['statut']));

        $app->setDateModification(new \DateTime());
        $this->em->flush();

        return $this->json(['message' => 'Application mise à jour avec succès', 'id' => $app->getId()]);
    }

    #[Route('/{id}/regenerate-key', name: 'regenerate_key', methods: ['PATCH', 'POST'])]
    public function regenerateKey(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $app = $this->applicationRepo->find($id);
        if (!$app) return $this->json(['error' => 'Application non trouvée'], 404);

        $newKey = $app->regenerateApiKey();
        $this->em->flush();

        return $this->json([
            'message' => 'Nouvelle clé SDK générée avec succès',
            'apiKey' => $newKey
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $app = $this->applicationRepo->find($id);
        if (!$app) return $this->json(['error' => 'Application non trouvée'], 404);

        // Détacher les missions associées
        foreach ($app->getMissions() as $m) {
            $m->setApplicationEntity(null);
        }

        $this->em->remove($app);
        $this->em->flush();

        return $this->json(['message' => 'Application supprimée avec succès']);
    }
}
