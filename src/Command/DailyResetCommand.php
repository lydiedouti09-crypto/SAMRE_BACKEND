<?php

namespace App\Command;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:daily-reset', description: 'Réinitialise les compteurs et prépare le nouveau code du jour pour les participations actives.')]
class DailyResetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParticipationRepository $participationRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $participations = $this->participationRepository->findAll();
        $count = 0;

        foreach ($participations as $participation) {
            if ($participation->getStatus() === 'terminee') {
                continue;
            }

            $participation->setJoursValides([]);
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf('Participations réinitialisées pour le nouveau jour : %d', $count));

        return Command::SUCCESS;
    }
}
