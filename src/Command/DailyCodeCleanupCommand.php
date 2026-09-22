<?php

namespace App\Command;

use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:mission-cleanup', description: 'Clôture les missions expirées et invalide les validations futures.')]
class DailyCodeCleanupCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MissionRepository $missionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $missions = $this->missionRepository->findAll();
        $count = 0;

        foreach ($missions as $mission) {
            if ($mission->getDateFin() && $mission->getDateFin() < new \DateTimeImmutable()) {
                $mission->setStatut('cloturee');
                $count++;
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf('Missions clôturées : %d', $count));

        return Command::SUCCESS;
    }
}
