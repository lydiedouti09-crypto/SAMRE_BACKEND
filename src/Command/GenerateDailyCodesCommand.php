<?php

namespace App\Command;

use App\Service\DailyCodeAutomationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:missions:generate-daily-codes',
    description: 'Génère automatiquement les codes quotidiens pour les panélistes actifs (J1 à J12)'
)]
class GenerateDailyCodesCommand extends Command
{
    public function __construct(
        private DailyCodeAutomationService $automationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule la commande sans persister en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🤖 Automatisation Samré : Génération des Codes Quotidiens de Test');

        $isDryRun = $input->getOption('dry-run');
        if ($isDryRun) {
            $io->note('Mode simulation activé (--dry-run)');
            return Command::SUCCESS;
        }

        $io->text('Recherche des participations actives et génération des codes du jour...');

        $result = $this->automationService->generateDailyCodesForAllActiveParticipations();

        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Participations actives analysées', $result['totalActiveParticipations']],
                ['Nouveaux codes générés pour aujourd\'hui', $result['generated']],
                ['Codes déjà existants / à jour', $result['existing']],
                ['Erreurs rencontrées', $result['errors']],
            ]
        );

        if ($result['errors'] > 0) {
            $io->warning(sprintf('%d erreur(s) signalée(s) pendant la génération.', $result['errors']));
        } else {
            $io->success(sprintf('Opération terminée avec succès ! %d nouveaux codes générés.', $result['generated']));
        }

        return Command::SUCCESS;
    }
}
