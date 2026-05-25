<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMysqlDump extends Command
{
    protected $signature = 'fambudget:import
                            {--file= : Percorso del file SQL (default: fambudget_mysql.sql nella root del progetto)}
                            {--force : Esegui senza chiedere conferma}';

    protected $description = 'Importa il dump SQL di FamBudget sul database configurato in .env';

    public function handle(): int
    {
        $file = $this->option('file') ?? base_path('fambudget_mysql.sql');

        if (!file_exists($file)) {
            $this->error("File non trovato: {$file}");
            return self::FAILURE;
        }

        $connection = config('database.default');
        $driver     = config("database.connections.{$connection}.driver");
        $database   = config("database.connections.{$connection}.database");
        $host       = config("database.connections.{$connection}.host", 'localhost');

        $this->info("Connessione : {$connection} ({$driver})");
        $this->info("Database   : {$database}" . ($driver === 'mysql' ? " @ {$host}" : ''));
        $this->info("File SQL   : {$file}");
        $this->newLine();

        if ($driver !== 'mysql') {
            $this->error("Il file è pensato per MySQL. Driver attuale: {$driver}");
            $this->line("Aggiorna DB_CONNECTION=mysql nel .env prima di procedere.");
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Procedere? Le tabelle esistenti verranno sovrascritte.', false)) {
            $this->line('Annullato.');
            return self::SUCCESS;
        }

        $sql = file_get_contents($file);

        // Dividi in statement singoli (ignora righe vuote e commenti)
        $statements = array_filter(
            array_map('trim', $this->splitStatements($sql)),
            fn($s) => $s !== '' && !str_starts_with($s, '--')
        );

        $total  = count($statements);
        $done   = 0;
        $errors = 0;

        $this->withProgressBar($statements, function ($statement) use (&$done, &$errors) {
            try {
                DB::unprepared($statement);
                $done++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn('ERRORE: ' . substr($statement, 0, 80) . '...');
                $this->error($e->getMessage());
            }
        });

        $this->newLine(2);
        $this->info("Completato: {$done}/{$total} statement eseguiti." . ($errors ? " ({$errors} errori)" : ' Nessun errore.'));

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $delimiter  = ';';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);

            // Salta commenti puri
            if (str_starts_with($trimmed, '--') || $trimmed === '') {
                continue;
            }

            $current .= $line . "\n";

            if (str_ends_with(rtrim($line), $delimiter)) {
                $statements[] = trim($current);
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return $statements;
    }
}
