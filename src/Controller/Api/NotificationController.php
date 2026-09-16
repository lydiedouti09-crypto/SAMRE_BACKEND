<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationRepository $repo
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $notifications = $this->repo->findBy(
            ['utilisateur' => $this->getUser()],
            ['dateCreation' => 'DESC']
        );
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