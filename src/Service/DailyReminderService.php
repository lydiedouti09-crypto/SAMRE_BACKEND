<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Participation;
use App\Repository\NotificationRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DailyReminderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParticipationRepository $participationRepo,
        private NotificationRepository $notificationRepo,
        private DailyCodeAutomationService $automationService
    ) {}

    /**
     * Analyse toutes les participations actives et génère les notifications de rappel automatiques (12h et 18h)
     */
    public function processReminders(): array
    {
        $now = new \DateTime();
        $hour = (int) $now->format('H');
        $todayStr = $now->format('Y-m-d');

        $activeParticipations = $this->participationRepo->createQueryBuilder('p')
            ->where('p.status IN (:statuses)')
            ->setParameter('statuses', ['en_cours', 'commencee', 'contrat_accepte'])
            ->getQuery()
            ->getResult();

        $remindersSent = 0;

        foreach ($activeParticipations as $participation) {
            $user = $participation->getUser();
            if (!$user) continue;

            $mission = $participation->getMission();
            $appName = $mission?->getApplication() ?: ($mission?->getTitre() ?: 'votre application');
            $currentDay = $this->automationService->calculateCurrentDay($participation);

            // Vérifier si la journée a déjà été validée aujourd'hui
            $todayValidation = $this->automationService->hasValidatedToday($participation);
            if ($todayValidation !== null) {
                // Déjà validé aujourd'hui, aucun rappel nécessaire
                continue;
            }

            // 1. Rappel de 12h00 (déclenché si l'heure est >= 12)
            if ($hour >= 12) {
                $remind12Key = sprintf('reminder_12h_%d_%s', $participation->getId(), $todayStr);
                if (!$this->hasNotificationWithType($user, $remind12Key)) {
                    $notif12 = new Notification();
                    $notif12->setUtilisateur($user);
                    $notif12->setTitre(sprintf('🔔 Rappel (12h) : Test du Jour %d sur %s', $currentDay, $appName));
                    $notif12->setMessage(sprintf(
                        "Il est 12h ! N'oubliez pas d'ouvrir et de tester %s aujourd'hui (Jour %d) puis de valider votre code quotidien sur votre espace Samré.",
                        $appName,
                        $currentDay
                    ));
                    $notif12->setType($remind12Key);
                    $notif12->setLu(false);
                    $notif12->setDateCreation(new \DateTime());
                    $this->em->persist($notif12);
                    $remindersSent++;
                }
            }

            // 2. Rappel urgent de 18h00 (déclenché si l'heure est >= 18)
            if ($hour >= 18) {
                $remind18Key = sprintf('reminder_18h_%d_%s', $participation->getId(), $todayStr);
                if (!$this->hasNotificationWithType($user, $remind18Key)) {
                    $notif18 = new Notification();
                    $notif18->setUtilisateur($user);
                    $notif18->setTitre(sprintf('⏳ Rappel urgent (18h) : Jour %d non validé sur %s', $currentDay, $appName));
                    $notif18->setMessage(sprintf(
                        "Attention, il vous reste quelques heures pour tester %s (Jour %d) ! Effectuez votre session de test et validez votre code avant 23h59 pour ne pas marquer ce jour comme manqué.",
                        $appName,
                        $currentDay
                    ));
                    $notif18->setType($remind18Key);
                    $notif18->setLu(false);
                    $notif18->setDateCreation(new \DateTime());
                    $this->em->persist($notif18);
                    $remindersSent++;
                }
            }
        }

        if ($remindersSent > 0) {
            $this->em->flush();
        }

        return [
            'success' => true,
            'remindersSent' => $remindersSent,
            'hour' => $hour,
            'date' => $todayStr,
        ];
    }

    private function hasNotificationWithType($user, string $type): bool
    {
        $existing = $this->notificationRepo->findOneBy([
            'utilisateur' => $user,
            'type' => $type,
        ]);
        return $existing !== null;
    }
}
