<?php

namespace App\Controller\Api;

use App\Entity\Commentaire;
use App\Entity\Etape;
use App\Entity\Mission;
use App\Entity\Notification;
use App\Entity\Participation;
use App\Entity\Reference;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\CommentaireRepository;
use App\Repository\EtapeRepository;
use App\Repository\MissionRepository;
use App\Repository\NotificationRepository;
use App\Repository\ParticipationRepository;
use App\Repository\ReferenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private MissionRepository $missionRepo,
        private ParticipationRepository $participationRepo,
        private CommentaireRepository $commentaireRepo,
        private EtapeRepository $etapeRepo,
        private ReferenceRepository $referenceRepo,
        private NotificationRepository $notificationRepo,
        private ApplicationRepository $applicationRepo,
        private EntityManagerInterface $em
    ) {}

    private function checkAdmin(): ?JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'admin') {
            return $this->json(['error' => 'Accès réservé aux administrateurs'], 403);
        }
        return null;
    }

    // ==========================================
    // 1. STATISTIQUES GLOBALES
    // ==========================================
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $chercheurs = $this->userRepo->findBy(['role' => 'chercheur']);
        $missions = $this->missionRepo->findAll();
        $participations = $this->participationRepo->findAll();
        $commentaires = $this->commentaireRepo->findAll();

        $enAttente = count(array_filter($participations, fn($p) => in_array($p->getStatus(), ['en_attente', 'postule'])));
        $enCours = count(array_filter($participations, fn($p) => in_array($p->getStatus(), ['en_cours', 'acceptee', 'commencee'])));
        $terminees = count(array_filter($participations, fn($p) => in_array($p->getStatus(), ['terminee', 'remuneration_payee'])));
        $retards = count(array_filter($participations, fn($p) => in_array($p->getStatus(), ['en_retard', 'retard'])));
        $abandons = count(array_filter($participations, fn($p) => in_array($p->getStatus(), ['abandonnee', 'abandon'])));

        return $this->json([
            'chercheurs' => count($chercheurs),
            'chercheursActifs' => count(array_filter($chercheurs, fn($u) => $u->getStatut() === 'actif')),
            'chercheursSuspendus' => count(array_filter($chercheurs, fn($u) => $u->getStatut() === 'suspendu')),
            'missions' => count($missions),
            'missionsDisponibles' => count(array_filter($missions, fn($m) => $m->getStatut() === 'disponible')),
            'missionsEnCours' => count(array_filter($missions, fn($m) => $m->getStatut() === 'en_cours')),
            'missionsSuspendues' => count(array_filter($missions, fn($m) => $m->getStatut() === 'suspendue')),
            'missionsTerminees' => count(array_filter($missions, fn($m) => $m->getStatut() === 'terminee')),
            'missionsArchivees' => count(array_filter($missions, fn($m) => $m->getStatut() === 'archivee')),
            'participations' => count($participations),
            'participationsEnAttente' => $enAttente,
            'participationsEnCours' => $enCours,
            'participationsTerminees' => $terminees,
            'participationsRetards' => $retards,
            'participationsAbandons' => $abandons,
            'feedbacks' => count($commentaires),
        ]);
    }

    // ==========================================
    // 2. GESTION DES UTILISATEURS / TESTEURS
    // ==========================================
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $users = $this->userRepo->findBy(['role' => 'chercheur'], ['id' => 'DESC']);
        $result = [];

        foreach ($users as $u) {
            $parts = $u->getParticipations();
            $partsData = [];
            foreach ($parts as $p) {
                $m = $p->getMission();
                $mEtapes = $m ? $m->getEtapes() : [];
                $totalJ = 1;
                foreach ($mEtapes as $et) {
                    if ($et->getJour() > $totalJ) $totalJ = $et->getJour();
                }
                $etComp = $p->getEtapesCompletees() ?: 0;
                $etTot = $p->getEtapesTotal() ?: (count($mEtapes) ?: 1);
                $jActuel = min($totalJ, (int)floor(($etComp / max(1, $etTot)) * $totalJ) + 1);

                $partsData[] = [
                    'id' => $p->getId(),
                    'missionId' => $m?->getId(),
                    'missionTitre' => $m?->getTitre() ?: 'Mission inconnue',
                    'application' => $m?->getApplication(),
                    'statut' => $p->getStatus(),
                    'progression' => $p->getProgression() ?: 0,
                    'etapesCompletees' => $etComp,
                    'etapesTotal' => $etTot,
                    'jourActuel' => $jActuel,
                    'totalJours' => $totalJ,
                ];
            }

            $result[] = [
                'id' => $u->getId(),
                'email' => $u->getEmail(),
                'nom' => $u->getNom(),
                'prenom' => $u->getPrenom(),
                'telephone' => $u->getTelephone(),
                'statut' => $u->getStatut() ?: 'actif',
                'photo' => $u->getPhoto(),
                'dateCreation' => $u->getDateCreation()?->format('Y-m-d H:i'),
                'totalMissions' => count($parts),
                'missionsTerminees' => count(array_filter($parts->toArray(), fn($p) => in_array($p->getStatus(), ['terminee', 'remuneration_payee']))),
                'participations' => $partsData,
            ];
        }

        return $this->json($result);
    }

    #[Route('/users/{id}/approve', name: 'approve_user', methods: ['PATCH'])]
    public function approveUser(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $user = $this->userRepo->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur introuvable'], 404);

        $user->setStatut('actif');
        $user->setDateModification(new \DateTime());
        $this->em->flush();

        // Notification envoyée au testeur
        $notif = new Notification();
        $notif->setUtilisateur($user);
        $notif->setTitre('Profil validé par SAMRE !');
        $notif->setMessage('Votre profil testeur a été validé par l\'administration. Vous pouvez désormais postuler aux missions de test.');
        $notif->setType('info');
        $notif->setLu(false);
        $notif->setDateCreation(new \DateTime());
        $this->em->persist($notif);
        $this->em->flush();

        return $this->json(['message' => 'Profil validé avec succès', 'statut' => 'actif']);
    }

    #[Route('/users/{id}/suspend', name: 'suspend_user', methods: ['PATCH'])]
    public function suspendUser(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $user = $this->userRepo->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur introuvable'], 404);

        $user->setStatut('suspendu');
        $user->setDateModification(new \DateTime());
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur suspendu', 'statut' => 'suspendu']);
    }

    #[Route('/users/{id}/reactivate', name: 'reactivate_user', methods: ['PATCH'])]
    public function reactivateUser(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $user = $this->userRepo->find($id);
        if (!$user) return $this->json(['error' => 'Utilisateur introuvable'], 404);

        $user->setStatut('actif');
        $user->setDateModification(new \DateTime());
        $this->em->flush();

        return $this->json(['message' => 'Utilisateur réactivé', 'statut' => 'actif']);
    }

    // ==========================================
    // UPLOAD IMAGE / LOGO APPLICATION
    // ==========================================
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $file = $request->files->get('image') ?: $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['error' => 'Format non autorisé. Utilisez JPG, PNG, WEBP ou SVG.'], 400);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/missions';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $filename = 'app_' . uniqid() . '.' . ($file->guessExtension() ?: 'png');
        $file->move($uploadsDir, $filename);

        return $this->json([
            'message' => 'Image uploadée avec succès',
            'url' => '/uploads/missions/' . $filename,
        ]);
    }

    // ==========================================
    // 3. GESTION DES MISSIONS
    // ==========================================
    #[Route('/missions', name: 'missions_list', methods: ['GET'])]
    public function listMissions(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $missions = $this->missionRepo->findBy([], ['id' => 'DESC']);
        $result = [];

        foreach ($missions as $m) {
            $etapes = [];
            foreach ($m->getEtapes() as $et) {
                $ref = $this->referenceRepo->findOneBy(['etape' => $et]);
                $etapes[] = [
                    'id' => $et->getId(),
                    'titre' => $et->getTitre(),
                    'instruction' => $et->getInstruction(),
                    'description' => $et->getDescription(),
                    'ordre' => $et->getOrdre(),
                    'jour' => $et->getJour() ?: 1,
                    'besoinReference' => $et->isBesoinReference(),
                    'codeReference' => $ref ? $ref->getReference() : null,
                    'resultatAttendu' => $et->getResultatAttendu(),
                    'dureeEstimee' => $et->getDureeEstimee(),
                    'statut' => $et->getStatut(),
                ];
            }

            // Calcul des jours max
            $maxJour = 1;
            foreach ($etapes as $et) {
                if ($et['jour'] > $maxJour) $maxJour = $et['jour'];
            }

            $result[] = [
                'id' => $m->getId(),
                'titre' => $m->getTitre(),
                'application' => $m->getApplication(),
                'applicationId' => $m->getApplicationEntity()?->getId(),
                'versionApplication' => $m->getVersionApplication() ?: '1.0.0',
                'platforme' => 'Android',
                'image' => $m->getImage(),
                'lienApplication' => $m->getLienApplication(),
                'dureEstime' => $m->getDureEstime(),
                'remuneration' => $m->getRemuneration(),
                'description' => $m->getDescription(),
                'objectif' => $m->getObjectif(),
                'conditionsParticipation' => $m->getConditionsParticipation(),
                'statut' => $m->getStatut() ?: 'disponible',
                'dateDebut' => $m->getDateDebut()?->format('Y-m-d'),
                'dateFin' => $m->getDateFin()?->format('Y-m-d'),
                'dateCreation' => $m->getDateCreation()?->format('Y-m-d H:i:s'),
                'nombreParticipants' => count($m->getParticipations()),
                'nombreParticipantsSouhaites' => $m->getNombreParticipantsSouhaites() ?: 20,
                'nombreEtapes' => count($etapes),
                'nombreJours' => $maxJour,
                'etapes' => $etapes,
            ];
        }

        return $this->json($result);
    }

    #[Route('/missions', name: 'missions_create', methods: ['POST'])]
    public function createMission(Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $data = json_decode($request->getContent(), true) ?? [];

        $titre = trim($data['titre'] ?? '');
        $application = trim($data['application'] ?? '');
        $appId = !empty($data['applicationId']) ? (int)$data['applicationId'] : null;
        $appEntity = $appId ? $this->applicationRepo->find($appId) : null;

        if (empty($titre) || (empty($application) && !$appEntity)) {
            return $this->json(['error' => 'Le titre et le nom de l\'application sont obligatoires.'], 400);
        }

        if ($appEntity && empty($application)) {
            $application = $appEntity->getNom();
        }

        $adminUser = $this->getUser();

        $mission = new Mission();
        $mission->setTitre($titre);
        $mission->setApplication($application);
        if ($appEntity) {
            $mission->setApplicationEntity($appEntity);
        }
        $mission->setVersionApplication($data['versionApplication'] ?? '1.0.0');
        $mission->setPlatforme('Android');
        $mission->setLienApplication($data['lienApplication'] ?? '');
        $mission->setDureEstime($data['dureEstime'] ?? '3 jours');
        $mission->setRemuneration((string)($data['remuneration'] ?? '0.00'));
        $mission->setDescription($data['description'] ?? 'Mission d\'évaluation et de test.');
        $mission->setObjectif($data['objectif'] ?? 'Vérifier la conformité du parcours utilisateur.');
        $mission->setConditionsParticipation($data['conditionsParticipation'] ?? 'Être actif sur Samré et accepter le contrat de testeur.');
        $mission->setStatut($data['statut'] ?? 'disponible');
        $mission->setNombreParticipantsSouhaites((int)($data['nombreParticipantsSouhaites'] ?? 50));
        $mission->setNombreParticipantsActuels(0);
        $mission->setImage(!empty($data['image']) ? $data['image'] : '/images/mission-default.png');
        $mission->setDateDebut(!empty($data['dateDebut']) ? new \DateTime($data['dateDebut']) : new \DateTime());
        $mission->setDateFin(!empty($data['dateFin']) ? new \DateTime($data['dateFin']) : (new \DateTime())->modify('+30 days'));
        $mission->setDateCreation(new \DateTime());
        if ($adminUser instanceof User) {
            $mission->setResponsable($adminUser);
        }

        $this->em->persist($mission);

        // Process etapes / Todo List par jour
        $etapesData = $data['etapes'] ?? [];
        if (empty($etapesData)) {
            $etapesData = [
                [
                    'jour' => 1,
                    'ordre' => 1,
                    'titre' => 'Installation et premier lancement',
                    'instruction' => 'Téléchargez l\'application depuis le lien officiel, lancez-la et explorez l\'interface.',
                    'besoinReference' => true,
                    'referenceCode' => 'SAMRE-J1-' . strtoupper(substr(uniqid(), -4)),
                ]
            ];
        }

        foreach ($etapesData as $idx => $eData) {
            $etape = new Etape();
            $etape->setMission($mission);
            $etape->setJour((int)($eData['jour'] ?? 1));
            $etape->setOrdre((int)($eData['ordre'] ?? ($idx + 1)));
            $etape->setTitre($eData['titre'] ?? ('Tâche ' . ($idx + 1)));
            $etape->setInstruction($eData['instruction'] ?? 'Suivez les instructions du test.');
            $etape->setDescription($eData['description'] ?? ($eData['instruction'] ?? ''));
            $etape->setResultatAttendu($eData['resultatAttendu'] ?? 'Étape validée avec succès.');
            $etape->setDureeEstimee($eData['dureeEstimee'] ?? '30 min');
            $etape->setStatut('actif');
            $etape->setDateCreation(new \DateTime());

            $besoinRef = !empty($eData['besoinReference']);
            $etape->setBesoinReference($besoinRef);
            $this->em->persist($etape);

            if ($besoinRef) {
                $refCode = trim($eData['referenceCode'] ?? '');
                if (empty($refCode)) {
                    $refCode = 'SAMRE-J' . ($etape->getJour()) . '-' . strtoupper(substr(uniqid(), -4));
                }
                $ref = new Reference();
                $ref->setMission($mission);
                $ref->setEtape($etape);
                $ref->setReference($refCode);
                $ref->setStatut('actif');
                $ref->setDateGeneration(new \DateTime());
                $ref->setDateExpiration((new \DateTime())->modify('+90 days'));
                $this->em->persist($ref);
            }
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Mission créée avec succès',
            'id' => $mission->getId(),
            'titre' => $mission->getTitre()
        ], 201);
    }

    #[Route('/missions/{id}', name: 'missions_update', methods: ['PUT', 'PATCH'])]
    public function updateMission(int $id, Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $mission = $this->missionRepo->find($id);
        if (!$mission) return $this->json(['error' => 'Mission non trouvée'], 404);

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['titre'])) $mission->setTitre(trim($data['titre']));
        if (isset($data['applicationId'])) {
            $appEntity = !empty($data['applicationId']) ? $this->applicationRepo->find((int)$data['applicationId']) : null;
            $mission->setApplicationEntity($appEntity);
        }
        if (isset($data['application'])) $mission->setApplication(trim($data['application']));
        if (isset($data['versionApplication'])) $mission->setVersionApplication(trim($data['versionApplication']));
        $mission->setPlatforme('Android');
        if (isset($data['image'])) $mission->setImage(trim($data['image']));
        if (isset($data['lienApplication'])) $mission->setLienApplication(trim($data['lienApplication']));
        if (isset($data['dureEstime'])) $mission->setDureEstime(trim($data['dureEstime']));
        if (isset($data['remuneration'])) $mission->setRemuneration((string)$data['remuneration']);
        if (isset($data['description'])) $mission->setDescription(trim($data['description']));
        if (isset($data['objectif'])) $mission->setObjectif(trim($data['objectif']));
        if (isset($data['conditionsParticipation'])) $mission->setConditionsParticipation(trim($data['conditionsParticipation']));
        if (isset($data['statut'])) $mission->setStatut(trim($data['statut']));
        if (isset($data['nombreParticipantsSouhaites'])) $mission->setNombreParticipantsSouhaites((int)$data['nombreParticipantsSouhaites']);
        if (!empty($data['dateDebut'])) $mission->setDateDebut(new \DateTime($data['dateDebut']));
        if (!empty($data['dateFin'])) $mission->setDateFin(new \DateTime($data['dateFin']));

        // Si des étapes sont transmises pour mise à jour
        if (isset($data['etapes']) && is_array($data['etapes'])) {
            // Nettoyage des anciennes références & étapes
            $oldRefs = $this->referenceRepo->findBy(['mission' => $mission]);
            foreach ($oldRefs as $r) $this->em->remove($r);
            foreach ($mission->getEtapes() as $et) $this->em->remove($et);
            $this->em->flush();

            foreach ($data['etapes'] as $idx => $eData) {
                $etape = new Etape();
                $etape->setMission($mission);
                $etape->setJour((int)($eData['jour'] ?? 1));
                $etape->setOrdre((int)($eData['ordre'] ?? ($idx + 1)));
                $etape->setTitre($eData['titre'] ?? ('Tâche ' . ($idx + 1)));
                $etape->setInstruction($eData['instruction'] ?? '');
                $etape->setDescription($eData['description'] ?? ($eData['instruction'] ?? ''));
                $etape->setResultatAttendu($eData['resultatAttendu'] ?? 'Validé');
                $etape->setDureeEstimee($eData['dureeEstimee'] ?? '30 min');
                $etape->setStatut('actif');
                $etape->setDateCreation(new \DateTime());

                $besoinRef = !empty($eData['besoinReference']);
                $etape->setBesoinReference($besoinRef);
                $this->em->persist($etape);

                if ($besoinRef) {
                    $refCode = trim($eData['referenceCode'] ?? '');
                    if (empty($refCode)) {
                        $refCode = 'SAMRE-J' . ($etape->getJour()) . '-' . strtoupper(substr(uniqid(), -4));
                    }
                    $ref = new Reference();
                    $ref->setMission($mission);
                    $ref->setEtape($etape);
                    $ref->setReference($refCode);
                    $ref->setStatut('actif');
                    $ref->setDateGeneration(new \DateTime());
                    $ref->setDateExpiration((new \DateTime())->modify('+90 days'));
                    $this->em->persist($ref);
                }
            }
        }

        $this->em->flush();

        return $this->json(['message' => 'Mission mise à jour avec succès', 'id' => $mission->getId()]);
    }

    #[Route('/missions/{id}/status', name: 'missions_change_status', methods: ['PATCH'])]
    public function changeMissionStatus(int $id, Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $mission = $this->missionRepo->find($id);
        if (!$mission) return $this->json(['error' => 'Mission non trouvée'], 404);

        $data = json_decode($request->getContent(), true) ?? [];
        $newStatus = trim($data['statut'] ?? '');

        $validStatuses = ['brouillon', 'disponible', 'en_cours', 'suspendue', 'terminee', 'archivee'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->json(['error' => 'Statut invalide.'], 400);
        }

        $mission->setStatut($newStatus);
        $this->em->flush();

        return $this->json(['message' => 'Statut de la mission modifié', 'statut' => $newStatus]);
    }

    #[Route('/missions/{id}', name: 'missions_delete', methods: ['DELETE'])]
    public function deleteMission(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $mission = $this->missionRepo->find($id);
        if (!$mission) return $this->json(['error' => 'Mission non trouvée'], 404);

        $refs = $this->referenceRepo->findBy(['mission' => $mission]);
        foreach ($refs as $r) $this->em->remove($r);

        $parts = $this->participationRepo->findBy(['mission' => $mission]);
        foreach ($parts as $p) $this->em->remove($p);

        foreach ($mission->getEtapes() as $et) $this->em->remove($et);

        $this->em->remove($mission);
        $this->em->flush();

        return $this->json(['message' => 'Mission supprimée avec succès']);
    }

    // ==========================================
    // 4. CANDIDATURES & PARTICIPATIONS
    // ==========================================
    #[Route('/participations', name: 'participations_list', methods: ['GET'])]
    public function participations(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $participations = $this->participationRepo->findBy([], ['id' => 'DESC']);
        $result = [];

        foreach ($participations as $p) {
            $user = $p->getUser();
            $mission = $p->getMission();

            $mEtapes = $mission ? $mission->getEtapes() : [];
            $totalJours = 1;
            foreach ($mEtapes as $et) {
                if ($et->getJour() > $totalJours) $totalJours = $et->getJour();
            }

            $etapesCompletees = $p->getEtapesCompletees() ?: 0;
            $etapesTotal = $p->getEtapesTotal() ?: (count($mEtapes) ?: 1);
            $jourActuel = min($totalJours, (int)floor(($etapesCompletees / max(1, $etapesTotal)) * $totalJours) + 1);

            $result[] = [
                'id' => $p->getId(),
                'testeurId' => $user?->getId(),
                'testeurNom' => $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Inconnu',
                'testeurEmail' => $user?->getEmail(),
                'testeurTelephone' => $user?->getTelephone(),
                'testeurPhoto' => $user?->getPhoto(),
                'missionId' => $mission?->getId(),
                'missionTitre' => $mission?->getTitre(),
                'application' => $mission?->getApplication(),
                'image' => $mission?->getImage(),
                'statut' => $p->getStatus() ?: 'en_attente',
                'progression' => $p->getProgression() ?: 0,
                'etapesCompletees' => $etapesCompletees,
                'etapesTotal' => $etapesTotal,
                'jourActuel' => $jourActuel,
                'totalJours' => $totalJours,
                'dateDebut' => $p->getDateDebut()?->format('Y-m-d H:i'),
                'contratAccepte' => $p->isContratAccepte(),
                'panelisteUid' => $p->getPanelisteUid() ?: ('TST-' . sprintf('%06d', $p->getId())),
                'dateCreation' => $p->getDateCreation()?->format('Y-m-d H:i'),
            ];
        }

        return $this->json($result);
    }

    #[Route('/participations/{id}/accept', name: 'participations_accept', methods: ['PATCH'])]
    public function acceptParticipation(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $participation = $this->participationRepo->find($id);
        if (!$participation) return $this->json(['error' => 'Participation non trouvée'], 404);

        if (!$participation->getPanelisteUid()) {
            $participation->setPanelisteUid('TST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
        }

        $participation->setStatus('acceptee');
        $participation->setDateDebut(new \DateTime());
        $this->em->flush();

        // Notification envoyée au testeur
        $user = $participation->getUser();
        $mission = $participation->getMission();
        if ($user) {
            $notif = new Notification();
            $notif->setUtilisateur($user);
            $notif->setTitre('Candidature acceptée !');

            $appNom = $mission?->getApplication() ?? ($mission?->getTitre() ?? 'l\'application');
            $downloadUrl = $mission?->getLienApplication() ?? $mission?->getApplicationEntity()?->getLienTelechargement();

            $msg = 'Félicitations, votre participation à la mission « ' . ($mission?->getTitre() ?? 'SAMRE') . ' » a été acceptée.';
            if ($downloadUrl && trim($downloadUrl) !== '') {
                $msg .= ' Téléchargez l\'application ' . $appNom . ' sur le Play Store : ' . trim($downloadUrl);
            } else {
                $msg .= ' Retrouvez le lien Google Play sur votre tableau de bord pour installer ' . $appNom . ' et débuter le test.';
            }

            $notif->setMessage($msg);
            $notif->setType('succes');
            $notif->setLu(false);
            $notif->setDateCreation(new \DateTime());
            $this->em->persist($notif);
            $this->em->flush();
        }

        return $this->json(['message' => 'Candidature acceptée avec succès', 'statut' => 'acceptee']);
    }

    #[Route('/participations/{id}/refuse', name: 'participations_refuse', methods: ['PATCH'])]
    public function refuseParticipation(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $participation = $this->participationRepo->find($id);
        if (!$participation) return $this->json(['error' => 'Participation non trouvée'], 404);

        $participation->setStatus('refusee');
        $this->em->flush();

        // Notification envoyée au testeur
        $user = $participation->getUser();
        $mission = $participation->getMission();
        if ($user) {
            $notif = new Notification();
            $notif->setUtilisateur($user);
            $notif->setTitre('Candidature non retenue');
            $notif->setMessage('Votre candidature pour la mission « ' . ($mission?->getTitre() ?? 'SAMRE') . ' » n\'a pas été retenue pour cette session.');
            $notif->setType('alerte');
            $notif->setLu(false);
            $notif->setDateCreation(new \DateTime());
            $this->em->persist($notif);
            $this->em->flush();
        }

        return $this->json(['message' => 'Candidature refusée', 'statut' => 'refusee']);
    }

    #[Route('/participations/{id}/status', name: 'participations_set_status', methods: ['PATCH'])]
    public function updateParticipationStatus(int $id, Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $participation = $this->participationRepo->find($id);
        if (!$participation) return $this->json(['error' => 'Participation non trouvée'], 404);

        $data = json_decode($request->getContent(), true) ?? [];
        $newStatus = trim($data['statut'] ?? '');

        $validStatuses = ['en_attente', 'acceptee', 'refusee', 'en_cours', 'en_retard', 'abandonnee', 'terminee'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->json(['error' => 'Statut de participation invalide'], 400);
        }

        $participation->setStatus($newStatus);
        if ($newStatus === 'terminee') {
            $participation->setProgression(100);
            $participation->setDateFin(new \DateTime());
        }
        $this->em->flush();

        return $this->json(['message' => 'Statut mis à jour', 'statut' => $newStatus]);
    }

    #[Route('/participations/{id}/details', name: 'participations_details', methods: ['GET'])]
    public function participationDetails(int $id): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $p = $this->participationRepo->find($id);
        if (!$p) return $this->json(['error' => 'Participation non trouvée'], 404);

        $user = $p->getUser();
        $mission = $p->getMission();

        $etapesDetails = [];
        if ($mission) {
            foreach ($mission->getEtapes() as $et) {
                // Trouver la référence associée à cette étape pour cette participation ou globale
                $ref = $this->referenceRepo->findOneBy(['etape' => $et, 'participation' => $p])
                    ?: $this->referenceRepo->findOneBy(['etape' => $et]);

                $etapesDetails[] = [
                    'id' => $et->getId(),
                    'titre' => $et->getTitre(),
                    'jour' => $et->getJour() ?: 1,
                    'ordre' => $et->getOrdre(),
                    'instruction' => $et->getInstruction(),
                    'besoinReference' => $et->isBesoinReference(),
                    'referenceAttendue' => $ref ? $ref->getReference() : null,
                    'referenceSaisie' => $ref ? $ref->getReferenceSaisie() : null,
                    'statutValidation' => $ref ? $ref->getStatut() : 'non_soumis',
                    'dateValidation' => $ref?->getDateValidation()?->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $this->json([
            'id' => $p->getId(),
            'panelisteUid' => $p->getPanelisteUid() ?: ('TST-' . sprintf('%06d', $p->getId())),
            'statut' => $p->getStatus(),
            'progression' => $p->getProgression() ?: 0,
            'contratAccepte' => $p->isContratAccepte(),
            'dateDebut' => $p->getDateDebut()?->format('Y-m-d H:i'),
            'dateFin' => $p->getDateFin()?->format('Y-m-d H:i'),
            'testeur' => [
                'id' => $user?->getId(),
                'nom' => $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Inconnu',
                'email' => $user?->getEmail(),
                'telephone' => $user?->getTelephone(),
                'statut' => $user?->getStatut(),
                'photo' => $user?->getPhoto(),
            ],
            'mission' => [
                'id' => $mission?->getId(),
                'titre' => $mission?->getTitre(),
                'application' => $mission?->getApplication(),
                'lienApplication' => $mission?->getLienApplication(),
                'dureEstime' => $mission?->getDureEstime(),
            ],
            'etapes' => $etapesDetails,
        ]);
    }

    // ==========================================
    // 5. FEEDBACKS / RETOURS DE TEST
    // ==========================================
    #[Route('/feedbacks', name: 'feedbacks_list', methods: ['GET'])]
    public function feedbacks(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $commentaires = $this->commentaireRepo->findBy([], ['id' => 'DESC']);
        $result = [];

        foreach ($commentaires as $c) {
            $user = $c->getUtilisateur();
            $part = $c->getParticipation();
            $mission = $part?->getMission();

            $result[] = [
                'id' => $c->getId(),
                'note' => $c->getNote(),
                'faciliteUtilisation' => $c->getFaciliteUtilisation(),
                'pointsPositifs' => $c->getPointsPositifs(),
                'problemes' => $c->getProblemes(),
                'difficultes' => $c->getDifficultes(),
                'ameliorations' => $c->getAmeliorations(),
                'commentaires' => $c->getCommentaires(),
                'dateCreation' => $c->getDateCreation()?->format('Y-m-d H:i'),
                'testeurNom' => $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Anonyme',
                'testeurEmail' => $user?->getEmail(),
                'testeurPhoto' => $user?->getPhoto(),
                'missionTitre' => $mission?->getTitre() ?: 'Mission générale',
                'missionId' => $mission?->getId(),
            ];
        }

        return $this->json($result);
    }

    // ==========================================
    // 6. CENTRE DE NOTIFICATIONS & MESSAGES
    // ==========================================
    #[Route('/notifications', name: 'notifications_list', methods: ['GET'])]
    public function notifications(): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $notifs = $this->notificationRepo->findBy([], ['id' => 'DESC'], 100);
        $result = [];

        foreach ($notifs as $n) {
            $user = $n->getUtilisateur();
            $result[] = [
                'id' => $n->getId(),
                'titre' => $n->getTitre(),
                'message' => $n->getMessage(),
                'type' => $n->getType() ?: 'info',
                'lu' => $n->isLu(),
                'dateCreation' => $n->getDateCreation()?->format('Y-m-d H:i'),
                'destinataireNom' => $user ? ($user->getPrenom() . ' ' . $user->getNom()) : 'Global',
                'destinataireEmail' => $user?->getEmail(),
            ];
        }

        return $this->json($result);
    }

    #[Route('/notifications/send', name: 'notifications_send', methods: ['POST'])]
    public function sendNotification(Request $request): JsonResponse
    {
        if ($err = $this->checkAdmin()) return $err;

        $data = json_decode($request->getContent(), true) ?? [];
        $titre = trim($data['titre'] ?? '');
        $message = trim($data['message'] ?? '');
        $type = trim($data['type'] ?? 'info');
        $target = trim($data['target'] ?? 'all'); // 'user' | 'mission' | 'all'
        $targetId = !empty($data['targetId']) ? (int)$data['targetId'] : null;

        if (empty($titre) || empty($message)) {
            return $this->json(['error' => 'Le titre et le message sont obligatoires'], 400);
        }

        $recipients = [];

        if ($target === 'user' && $targetId) {
            $u = $this->userRepo->find($targetId);
            if ($u) $recipients[] = $u;
        } elseif ($target === 'mission' && $targetId) {
            $mission = $this->missionRepo->find($targetId);
            if ($mission) {
                foreach ($mission->getParticipations() as $p) {
                    if ($p->getUser()) $recipients[] = $p->getUser();
                }
            }
        } else {
            // All testeurs
            $recipients = $this->userRepo->findBy(['role' => 'chercheur', 'statut' => 'actif']);
        }

        $recipients = array_unique($recipients, SORT_REGULAR);
        $count = 0;

        foreach ($recipients as $user) {
            if (!$user instanceof User) continue;
            $notif = new Notification();
            $notif->setUtilisateur($user);
            $notif->setTitre($titre);
            $notif->setMessage($message);
            $notif->setType($type);
            $notif->setLu(false);
            $notif->setDateCreation(new \DateTime());
            $this->em->persist($notif);
            $count++;
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Notification envoyée avec succès',
            'destinataires' => $count
        ], 201);
    }
}