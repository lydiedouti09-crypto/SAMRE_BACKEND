<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Etape;
use App\Entity\Mission;
use App\Entity\Participation;
use App\Entity\Reference;
use App\Repository\ApplicationRepository;
use App\Repository\EtapeRepository;
use App\Repository\MissionRepository;
use App\Repository\ParticipationRepository;
use App\Repository\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DailyCodeAutomationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicationRepository $applicationRepo,
        private MissionRepository $missionRepo,
        private ParticipationRepository $participationRepo,
        private EtapeRepository $etapeRepo,
        private ReferenceRepository $referenceRepo,
        private ?LoggerInterface $logger = null,
        private string $codePrefix = 'SAMRE'
    ) {}

    /**
     * Génère automatiquement les codes du jour pour toutes les participations actives
     */
    public function generateDailyCodesForAllActiveParticipations(): array
    {
        $activeStatuses = ['en_cours', 'commencee', 'acceptee'];
        $participations = $this->participationRepo->createQueryBuilder('p')
            ->where('p.status IN (:statuses)')
            ->setParameter('statuses', $activeStatuses)
            ->getQuery()
            ->getResult();

        $generatedCount = 0;
        $alreadyExistedCount = 0;
        $errorsCount = 0;

        foreach ($participations as $participation) {
            try {
                $ref = $this->ensureDailyCodeForParticipation($participation);
                if ($ref->getDateGeneration()->format('Y-m-d') === (new \DateTime())->format('Y-m-d') && $ref->getStatut() === 'generee') {
                    $generatedCount++;
                } else {
                    $alreadyExistedCount++;
                }
            } catch (\Throwable $e) {
                $errorsCount++;
                $this->logger?->error('Erreur génération code quotidien : ' . $e->getMessage(), [
                    'participation_id' => $participation->getId(),
                ]);
            }
        }

        $this->em->flush();

        return [
            'totalActiveParticipations' => count($participations),
            'generated' => $generatedCount,
            'existing' => $alreadyExistedCount,
            'errors' => $errorsCount,
        ];
    }

    /**
     * Assure qu'un code quotidien est disponible pour le jour courant d'une participation
     */
    public function ensureDailyCodeForParticipation(Participation $participation): Reference
    {
        $mission = $participation->getMission();
        if (!$mission) {
            throw new \RuntimeException('Participation sans mission associée.');
        }

        // Assurer que le panéliste a un identifiant unique
        if (!$participation->getPanelisteUid()) {
            $participation->setPanelisteUid('TST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
            $this->em->persist($participation);
        }

        // Déterminer le jour actuel de la mission pour ce testeur
        $currentDay = $this->calculateCurrentDay($participation);

        // Trouver ou créer l'étape du jour
        $etape = $this->getOrCreateEtapeForDay($mission, $currentDay);

        return $this->createReferenceForEtape($participation, $etape);
    }

    /**
     * Crée ou retourne la référence unique standardisée pour une étape et une participation données
     */
    public function createReferenceForEtape(Participation $participation, Etape $etape): Reference
    {
        // Vérifier si une référence existe déjà pour cette participation et cette étape
        $existingRef = $this->referenceRepo->findOneBy([
            'participation' => $participation,
            'etape' => $etape,
        ]);

        if ($existingRef) {
            return $existingRef;
        }

        $mission = $participation->getMission();
        $app = $mission?->getApplicationEntity();

        // Préfixe basé sur l'application ou la mission
        $appName = $app?->getNom() ?: ($mission?->getApplication() ?: '');
        $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $appName);
        $appPrefix = !empty($cleanName) ? strtoupper(substr($cleanName, 0, 4)) : $this->codePrefix;

        $jour = $etape->getJour() ?: $this->calculateCurrentDay($participation);
        $dailyCode = sprintf('%s-J%02d-%s', $appPrefix, $jour, strtoupper(bin2hex(random_bytes(3))));

        $ref = new Reference();
        $ref->setMission($mission);
        $ref->setParticipation($participation);
        $ref->setEtape($etape);
        $ref->setReference($dailyCode);
        $ref->setDateGeneration(new \DateTime());
        $ref->setDateExpiration((new \DateTime())->modify('+48 hours'));
        $ref->setStatut('generee');

        $this->em->persist($ref);
        $this->em->flush();

        return $ref;
    }

    /**
     * Calcule le jour de test en cours (ex: Jour 1 à 12)
     */
    public function calculateCurrentDay(Participation $participation): int
    {
        $mission = $participation->getMission();
        $maxDays = $mission?->getApplicationEntity()?->getDureeJoursDefaut() ?: 12;

        $dateDebut = $participation->getDateDebut() ?: $participation->getDateCreation() ?: new \DateTime();
        $now = new \DateTime();

        $diffDays = (int)$dateDebut->diff($now)->format('%a') + 1;
        $completedDays = (int)$participation->getEtapesCompletees();

        // Le jour courant correspond au prochain jour à réaliser (au minimum $completedDays + 1)
        $currentDay = max($diffDays, $completedDays + 1);

        return max(1, min($maxDays, $currentDay));
    }

    /**
     * Assure que toutes les étapes quotidiennes (1 à 12) existent pour la mission
     */
    public function ensureAllDailyEtapesForMission(Mission $mission): array
    {
        $app = $mission->getApplicationEntity();
        $totalDays = $app?->getDureeJoursDefaut() ?: 12;

        $etapes = [];
        for ($day = 1; $day <= $totalDays; $day++) {
            $etapes[] = $this->getOrCreateEtapeForDay($mission, $day);
        }
        return $etapes;
    }

    /**
     * Récupère ou génère à la volée une étape pour un jour donné
     */
    public function getOrCreateEtapeForDay(Mission $mission, int $day): Etape
    {
        $etape = $this->etapeRepo->findOneBy([
            'mission' => $mission,
            'jour' => $day,
        ]);

        if (!$etape) {
            $etape = new Etape();
            $etape->setMission($mission);
            $etape->setJour($day);
            $etape->setOrdre($day);
            $etape->setTitre('Test quotidien - Jour ' . $day);
            $etape->setInstructions('Ouvrez l\'application testée sur votre smartphone, effectuez vos parcours de test et saisissez votre code du jour dans le formulaire pour valider le Jour ' . $day . '.');
            $etape->setDescription('Test journalier (Jour ' . $day . ' sur 12)');
            $etape->setResultatAttendu('Code validé dans l\'application testée');
            $etape->setBesoinReference(true);
            $etape->setDureeEstimee('15-20 min');
            $etape->setStatut('actif');
            $etape->setDateCreation(new \DateTime());

            $this->em->persist($etape);
            $this->em->flush();
        }

        return $etape;
    }

    /**
     * Vérifie et valide le code transmis par le formulaire de l'application testée via le SDK
     */
    public function verifySdkCode(string $apiKey, string $panelisteUid, string $submittedCode): array
    {
        $apiKey = trim($apiKey);
        $panelisteUid = trim($panelisteUid);
        $submittedCode = strtoupper(trim($submittedCode));

        if (empty($apiKey) || empty($panelisteUid) || empty($submittedCode)) {
            return [
                'success' => false,
                'error' => 'Paramètres incomplets : apiKey, panelisteId et code sont requis.',
                'code' => 400
            ];
        }

        // 1. Vérifier l'application
        $app = $this->applicationRepo->findByApiKey($apiKey);
        if (!$app) {
            return [
                'success' => false,
                'error' => 'Clé d\'intégration SDK invalide ou application introuvable.',
                'code' => 401
            ];
        }

        // 2. Trouver la participation correspondante au panéliste
        $participation = $this->participationRepo->findOneBy(['panelisteUid' => $panelisteUid]);
        if (!$participation) {
            return [
                'success' => false,
                'error' => 'Identifiant panéliste introuvable. Veuillez vérifier votre ID dans Samré.',
                'code' => 404
            ];
        }

        $mission = $participation->getMission();
        // Vérifier que la mission correspond bien à l'application testée
        if ($mission && $mission->getApplicationEntity() && $mission->getApplicationEntity()->getId() !== $app->getId()) {
            return [
                'success' => false,
                'error' => 'Ce panéliste n\'est pas inscrit au projet correspondant à cette application.',
                'code' => 403
            ];
        }

        // 3. Trouver la référence correspondante
        $currentDay = $this->calculateCurrentDay($participation);

        // On cherche la référence pour aujourd'hui ou non encore validée
        $reference = $this->referenceRepo->createQueryBuilder('r')
            ->join('r.etape', 'e')
            ->where('r.participation = :part')
            ->andWhere('UPPER(r.reference) = :code')
            ->setParameter('part', $participation)
            ->setParameter('code', $submittedCode)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$reference) {
            $currentRef = $this->ensureDailyCodeForParticipation($participation);
            if (strtoupper($currentRef->getReference()) === $submittedCode) {
                $reference = $currentRef;
            }
        }

        if (!$reference) {
            return [
                'success' => false,
                'error' => 'Code de validation incorrect pour ce panéliste. Veuillez vérifier le code affiché sur votre espace Samré.',
                'code' => 422
            ];
        }

        if ($reference->getStatut() === 'validee') {
            return [
                'success' => true,
                'alreadyValidated' => true,
                'message' => 'Ce jour a déjà été validé avec succès !',
                'jour' => $reference->getEtape()?->getJour() ?: $currentDay,
                'progression' => $participation->getProgression(),
            ];
        }

        // 4. Valider la référence et l'étape
        $reference->setReferenceSaisie($submittedCode);
        $reference->setStatut('validee');
        $reference->setDateValidation(new \DateTime());

        $etape = $reference->getEtape();
        if ($etape) {
            $etape->setStatut('validee');
        }

        // 5. Calculer la progression
        $allEtapes = $mission ? $mission->getEtapes() : [];
        $totalEtapes = count($allEtapes) ?: ($app->getDureeJoursDefaut() ?: 12);

        $valideesCount = $this->referenceRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.participation = :part')
            ->andWhere('r.statut = :val')
            ->setParameter('part', $participation)
            ->setParameter('val', 'validee')
            ->getQuery()
            ->getSingleScalarResult();

        $progression = min(100, (int)round(($valideesCount / max(1, $totalEtapes)) * 100));

        $participation->setEtapesCompletees($valideesCount);
        $participation->setEtapesTotal($totalEtapes);
        $participation->setProgression($progression);

        if ($valideesCount >= $totalEtapes) {
            $participation->setStatus('terminee');
            $participation->setDateFin(new \DateTime());
        }

        $this->em->flush();

        return [
            'success' => true,
            'alreadyValidated' => false,
            'message' => 'Félicitations ! Journée validée avec succès.',
            'jour' => $etape?->getJour() ?: $currentDay,
            'progression' => $progression,
            'application' => $app->getNom(),
            'paneliste' => $participation->getUser() ? ($participation->getUser()->getPrenom() . ' ' . $participation->getUser()->getNom()) : 'Panéliste',
        ];
    }
}
