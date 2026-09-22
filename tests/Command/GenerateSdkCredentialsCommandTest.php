<?php

namespace App\Tests\Command;

use App\Command\GenerateSdkCredentialsCommand;
use App\Entity\Application;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateSdkCredentialsCommandTest extends TestCase
{
    public function testItDoesNotExposeSecretKeyInOutput(): void
    {
        $application = new Application();
        $application->setNom('Demo app');
        $secret = $application->getSecretKey();

        $repository = $this->createMock(ApplicationRepository::class);
        $repository->method('find')->with(42)->willReturn($application);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $command = new GenerateSdkCredentialsCommand($repository, $entityManager);
        $tester = new CommandTester($command);

        $tester->execute(['appId' => 42]);

        $output = $tester->getDisplay();

        $this->assertStringNotContainsString($secret, $output);
        $this->assertStringNotContainsString('secretKey:', $output);
        $this->assertStringContainsString('apiKey:', $output);
        $this->assertStringContainsString('sdkToken:', $output);
    }
}
