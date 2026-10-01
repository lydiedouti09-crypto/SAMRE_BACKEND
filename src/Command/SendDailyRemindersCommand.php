<?php

namespace App\Command;

use App\Service\DailyReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'samre:send-reminders',
    description: 'Envoie les rappels automatiques (12h et 18h) aux testeurs n\'ayant pas encore validé leur journée.'
)]
class SendDailyRemindersCommand extends Command
{
    public function __construct(
        private DailyReminderService $reminderService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Envoi des rappels automatiques aux testeurs Samré (12h / 18h)');

        $result = $this->reminderService->processReminders();

        $io->success(sprintf(
            'Traitement terminé avec succès. %d rappel(s) envoyé(s) pour l\'heure %dh (Date: %s).',
            $result['remindersSent'],
            $result['hour'],
            $result['date']
        ));

        return Command::SUCCESS;
    }
}
