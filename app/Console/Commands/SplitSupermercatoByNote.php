<?php

namespace App\Console\Commands;

use App\Models\BudgetCategory;
use App\Models\BudgetExpense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SplitSupermercatoByNote extends Command
{
    protected $signature = 'fambudget:split-supermercato
                            {--dry-run : Mostra cosa verrebbe fatto senza modificare nulla}
                            {--force : Esegui senza chiedere conferma}';

    protected $description = 'Crea una voce di spesa per ogni nota distinta sotto Supermercato e riassegna le spese';

    public function handle(): int
    {
        $source = BudgetCategory::where('categoria', 'SPESA')->where('nome', 'Supermercato')->first();

        if (!$source) {
            $this->error('Categoria SPESA / Supermercato non trovata.');
            return self::FAILURE;
        }

        $spese = BudgetExpense::where('budget_category_id', $source->id)
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->get();

        if ($spese->isEmpty()) {
            $this->info('Nessuna spesa con nota trovata sotto Supermercato.');
            return self::SUCCESS;
        }

        $gruppi = $spese->groupBy('note');

        $this->info("Categoria sorgente: [{$source->id}] SPESA / Supermercato");
        $this->newLine();
        $this->line(sprintf('%-30s %6s %10s', 'Nota → nuova voce', 'Spese', 'Totale'));
        $this->line(str_repeat('─', 50));

        foreach ($gruppi as $nota => $items) {
            $this->line(sprintf('%-30s %6d %10s €',
                $nota,
                $items->count(),
                number_format($items->sum('importo'), 2, ',', '.')
            ));
        }

        $this->newLine();
        $this->line(sprintf('Totale: %d spese in %d voci', $spese->count(), $gruppi->count()));
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('--dry-run: nessuna modifica eseguita.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Procedere? Verranno create le nuove voci e riassegnate le spese.', false)) {
            $this->line('Annullato.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($source, $gruppi) {
            foreach ($gruppi as $nota => $items) {
                $existing = BudgetCategory::where('categoria', 'SPESA')->where('nome', $nota)->first();

                if ($existing) {
                    $cat = $existing;
                    $this->line("  [già esiste] SPESA / {$nota} (id: {$cat->id})");
                } else {
                    $cat = BudgetCategory::create([
                        'categoria'       => 'SPESA',
                        'nome'            => $nota,
                        'importo_annuale' => 0,
                        'importo_mensile' => 0,
                        'sort_order'      => $source->sort_order,
                    ]);
                    $this->line("  [creata] SPESA / {$nota} (id: {$cat->id})");
                }

                BudgetExpense::whereIn('id', $items->pluck('id'))
                    ->update(['budget_category_id' => $cat->id, 'note' => null]);
            }
        });

        $this->newLine();
        $remaining = BudgetExpense::where('budget_category_id', $source->id)->count();
        $this->info("Fatto. Spese rimaste su Supermercato: {$remaining}");

        if ($remaining === 0) {
            $this->line("La categoria Supermercato (id: {$source->id}) è ora vuota. Puoi eliminarla dal pannello Imposta Budget.");
        }

        return self::SUCCESS;
    }
}
