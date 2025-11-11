<?php

// ===== Command/ProcessOverdueLoansCommand.php =====
namespace App\Command;

use App\Service\LoanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande Symfony pour traiter les emprunts en retard
 *
 * Design Pattern : Command
 * Encapsule une action dans un objet exécutable
 *
 * Utilisation :
 * php bin/console app:process-overdue-loans
 *
 * Peut être ajouté au crontab :
 * 0 9 * * * cd /path/to/project && php bin/console app:process-overdue-loans
 */
#[AsCommand(
    name: 'app:process-overdue-loans',
    description: 'Process overdue loans and send reminders',
)]
class ProcessOverdueLoansCommand extends Command
{
    public function __construct(
        private LoanService $loanService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command processes all overdue loans and sends reminder emails to users.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Processing Overdue Loans');

        try {
            $count = $this->loanService->processOverdueLoans();

            $io->success(sprintf('Successfully processed %d overdue loan(s).', $count));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
