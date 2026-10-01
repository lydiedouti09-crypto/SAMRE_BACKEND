<?php

namespace App\Repository;

use App\Entity\Application;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    public function findByApiKey(string $apiKey): ?Application
    {
        $apiKey = trim($apiKey);
        if (empty($apiKey)) return null;

        return $this->findOneBy(['apiKey' => $apiKey])
            ?: $this->findOneBy(['tokenIntegration' => $apiKey]);
    }

    public function findByTokenIntegration(string $token): ?Application
    {
        $token = trim($token);
        if (empty($token)) return null;

        return $this->findOneBy(['tokenIntegration' => $token])
            ?: $this->findOneBy(['apiKey' => $token]);
    }
}
