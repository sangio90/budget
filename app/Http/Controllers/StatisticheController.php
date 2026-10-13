<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\BudgetExpense;
use Illuminate\Http\Request;

class StatisticheController extends Controller
{
    private const ANNO_INIZIO    = 2025;
    private const TOP_CATEGORIE  = 5;
    public const ALTRE           = 'Altre categorie';

    // Palette categoriale validata (8 colori); il gruppo "Altre categorie" è sempre grigio
    private const PALETTE        = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
    private const COLORE_ALTRE   = '#7a7974';

    public function index(Request $request)
    {
        $anni = range(self::ANNO_INIZIO, max(self::ANNO_INIZIO, now()->year));

        $anno = (int) $request->input('anno', now()->year);
        if (! in_array($anno, $anni, true)) {
            $anno = end($anni);
        }

        // Raggruppamento per mese/categoria in PHP
        $spese = BudgetExpense::with('category:id,categoria')
            ->where('user_id', auth()->id())
            ->whereYear('data', $anno)
            ->get(['budget_category_id', 'data', 'importo'])
            ->filter(fn($e) => $e->data !== null);

        $categoriaDi = fn($e) => $e->category?->categoria ?? self::ALTRE;

        // Le 5 categorie con più spesa nell'anno, il resto confluisce in "Altre categorie"
        $top = $spese->groupBy($categoriaDi)
            ->map(fn($rows) => $rows->sum('importo'))
            ->sortDesc()
            ->keys()
            ->reject(fn($c) => $c === self::ALTRE)
            ->take(self::TOP_CATEGORIE)
            ->values();

        $serie = $top->push(self::ALTRE)->mapWithKeys(fn($c) => [$c => array_fill(0, 12, 0.0)])->all();
        foreach ($spese as $e) {
            $cat = $categoriaDi($e);
            $cat = isset($serie[$cat]) ? $cat : self::ALTRE;
            $serie[$cat][$e->data->month - 1] += (float) $e->importo;
        }
        $serie = array_map(fn($mesi) => array_map(fn($v) => round($v, 2), $mesi), $serie);
        if (array_sum($serie[self::ALTRE]) == 0) {
            unset($serie[self::ALTRE]);
        }

        $mesi = [];
        for ($m = 0; $m < 12; $m++) {
            $mesi[] = round(array_sum(array_column($serie, $m)), 2);
        }

        $budgetMensile = BudgetCategory::with('amounts')->get()
            ->sum(fn($c) => $c->importiPerAnno($anno)['mensile']);

        // Per l'anno in corso la media considera solo i mesi trascorsi
        $mesiTrascorsi = $anno === now()->year ? now()->month : 12;
        $totale        = array_sum($mesi);
        $media         = $totale / $mesiTrascorsi;
        $meseMax       = $totale > 0 ? array_search(max($mesi), $mesi) + 1 : null;

        return view('statistiche.index', [
            'anni'          => $anni,
            'anno'          => $anno,
            'serie'         => $serie,
            'colori'        => $this->coloriCategorie(array_keys($serie)),
            'mesi'          => $mesi,
            'budgetMensile' => round($budgetMensile, 2),
            'totale'        => $totale,
            'media'         => $media,
            'meseMax'       => $meseMax,
        ]);
    }

    /**
     * Colore stabile per nome: ogni categoria ha un colore preferito (hash del nome).
     * Se due categorie dello stesso grafico lo condividono, la seconda in ordine
     * alfabetico prende il primo colore libero successivo.
     */
    private function coloriCategorie(array $categorie): array
    {
        $colori = [];
        $usati  = [];
        $nomi   = array_values(array_filter($categorie, fn($c) => $c !== self::ALTRE));
        sort($nomi);

        foreach ($nomi as $nome) {
            $n   = count(self::PALETTE);
            $idx = crc32(mb_strtoupper(trim($nome))) % $n;
            for ($k = 0; $k < $n && isset($usati[$idx]); $k++) {
                $idx = ($idx + 1) % $n;
            }
            $usati[$idx]   = true;
            $colori[$nome] = self::PALETTE[$idx];
        }
        $colori[self::ALTRE] = self::COLORE_ALTRE;

        return $colori;
    }
}
