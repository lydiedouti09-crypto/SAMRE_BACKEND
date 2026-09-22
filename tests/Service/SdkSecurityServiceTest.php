<?php

namespace App\Tests\Service;

use App\Entity\Participation;
use App\Repository\ApplicationRepository;
use App\Repository\ParticipationRepository;
use App\Service\SdkSecurityService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SdkSecurityServiceTest extends TestCase
{
    public function testReplayDetectionIsTriggeredForSameDeviceOrIp(): void
    {
        $participation = new Participation();
        $participation->setStatus('active');
        $participation->setValidationHistory([
            ['deviceId' => 'device-1', 'ipAddress' => '127.0.0.1', 'jour' => 1, 'timestamp' => '2026-09-22T00:00:00+00:00'],
        ]);

        $repository = $this->createMock(ParticipationRepository::class);
        $repository->method('find')->with(7)->willReturn($participation);

        $appRepository = $this->createMock(ApplicationRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $service = new SdkSecurityService($appRepository, $repository, $entityManager);

        $this->assertTrue($service->isReplayAttempt(7, 'device-1', '127.0.0.2'));
        $this->assertTrue($service->isReplayAttempt(7, 'device-9', '127.0.0.1'));
        $this->assertFalse($service->isReplayAttempt(7, 'device-9', '127.0.0.9'));
    }
}
