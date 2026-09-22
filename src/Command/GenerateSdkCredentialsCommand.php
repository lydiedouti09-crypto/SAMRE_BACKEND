<?php

namespace App\Command;

use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sdk:generate-credentials', description: 'Génère les identifiants SDK d’une application sans exposer le secret HMAC.')]
class GenerateSdkCredentialsCommand extends Command
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('appId', InputArgument::REQUIRED, 'ID de l’application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $appId = (int) $input->getArgument('appId');
        $app = $this->applicationRepository->find($appId);

        if (!$app) {
            $output->writeln('<error>Application introuvable.</error>');
            return Command::FAILURE;
        }

        $app->regenerateSdkToken();
        $app->regenerateSecretKey();
        $this->entityManager->flush();

        $output->writeln('Application: ' . $app->getNom());
        $output->writeln('appId: ' . $app->getId());
        $output->writeln('apiKey: ' . $app->getApiKey());
        $output->writeln('sdkToken: ' . $app->getSdkToken());
        $output->writeln('<comment>Le secret HMAC reste strictement côté backend et n’est jamais exposé.</comment>');

        return Command::SUCCESS;
    }
}
