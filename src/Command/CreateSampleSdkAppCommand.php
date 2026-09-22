<?php

namespace App\Command;

use App\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:sdk:create-demo', description: 'Crée une application de démonstration avec ses credentials SDK.')]
class CreateSampleSdkAppCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Nom de l’application', 'Demo SDK');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = new Application();
        $app->setNom((string) $input->getArgument('name'));
        $app->setDescription('Application de démonstration pour le SDK Samre.');
        $app->setPlateforme('Android');
        $app->setVersion('1.0.0');
        $app->setStatut('active');
        $this->entityManager->persist($app);
        $this->entityManager->flush();

        $output->writeln('Application créée : ' . $app->getNom());
        $output->writeln('appId: ' . $app->getId());
        $output->writeln('apiKey: ' . $app->getApiKey());
        $output->writeln('sdkToken: ' . $app->getSdkToken());
        $output->writeln('<comment>Le secret HMAC reste strictement côté backend et n’est jamais exposé au SDK.</comment>');

        return Command::SUCCESS;
    }
}
