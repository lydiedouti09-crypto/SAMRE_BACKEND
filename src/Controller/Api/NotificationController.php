<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationRepository $repo,
        private ParticipationRepository $participationRepo
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $this->repo->findBy(
            ['utilisateur' => $user],
            ['dateCreation' => 'DESC']
        );

        $updated = false;
        foreach ($notifications as $n) {
            if (stripos($n->getTitre(), 'acceptée') !== false && stripos($n->getMessage(), 'http') === false) {
                $parts = $this->participationRepo->findBy(['User' => $user]);
                $matchedLink = null;
                $matchedApp = null;
                foreach ($parts as $p) {
                    $m = $p->getMission();
                    if ($m && (stripos($n->getMessage(), $m->getApplication()) !== false || stripos($n->getMessage(), $m->getTitre()) !== false)) {
                        $matchedLink = $m->getLienApplication() ?? $m->getApplicationEntity()?->getLienTelechargement();
                        $matchedApp = $m->getApplication();
                        break;
                    }
                }
                if (!$matchedLink && !empty($parts)) {
                    $m = $parts[0]->getMission();
                    $matchedLink = $m?->getLienApplication() ?? $m?->getApplicationEntity()?->getLienTelechargement();
                    $matchedApp = $m?->getApplication();
                }

                $storeUrl = $matchedLink ?: ('https://play.google.com/store/search?q=' . urlencode($matchedApp ?: 'FlyPoint') . '&c=apps');
                $msg = rtrim(trim($n->getMessage()), '.');
                $n->setMessage($msg . '. Lien Google Play Store : ' . $storeUrl);
                $this->em->persist($n);
                $updated = true;
            }
        }
        if ($updated) {
            $this->em->flush();
        }

        return $this->json($notifications, 200, [], ['groups' => 'notification:read']);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['PATCH'])]
    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $notification->setLu(true);
        $this->em->flush();

        return $this->json($notification, 200, [], ['groups' => 'notification:read']);
    }

    #[Route('/read-all', name: 'mark_all_read', methods: ['PATCH'])]
    public function markAllRead(): JsonResponse
    {
        $notifications = $this->repo->findBy([
            'utilisateur' => $this->getUser(),
            'lu' => false
        ]);

        foreach ($notifications as $n) {
            $n->setLu(true);
        }
        $this->em->flush();

        return $this->json(['message' => 'All marked as read']);
    }
}