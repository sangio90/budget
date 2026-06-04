<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use App\Models\BudgetExpense;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $categories = BudgetCategory::orderBy('sort_order')->orderBy('categoria')->get();

        $notePerCategoria = BudgetExpense::where('user_id', auth()->id())
            ->whereNotNull('note')->where('note', '!=', '')
            ->selectRaw('budget_category_id, note')
            ->distinct()
            ->get()
            ->groupBy('budget_category_id')
            ->map(fn($rows) => $rows->pluck('note')->sort()->values());

        return view('budget.index', compact('categories', 'notePerCategoria'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'importo' => 'required|numeric|min:0.01',
            'data' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        BudgetExpense::create(['user_id' => auth()->id()] + $validated);

        return redirect()->route('budget.index')
            ->with('success', 'Spesa inserita correttamente.')
            ->with('last_category_id', $validated['budget_category_id']);
    }

    public function spese(Request $request)
    {
        $anno = $request->input('anno', now()->year);
        $mese = $request->input('mese', now()->month);
        $categoriaFiltro = $request->input('categoria', '');

        $categories = BudgetCategory::with('amounts')->orderBy('sort_order')->orderBy('categoria')->get();
        $categorie = $categories->pluck('categoria')->unique()->values();

        $query = BudgetExpense::with('category')
            ->where('user_id', auth()->id())
            ->whereYear('data', $anno);

        if ($mese) {
            $query->whereMonth('data', $mese);
        }

        if ($categoriaFiltro) {
            $query->whereHas('category', fn($q) => $q->where('categoria', $categoriaFiltro));
        }

        $spese = $query->orderByDesc('data')->orderByDesc('id')->get();

        $totale = $spese->sum('importo');

        $grafico = null;
        if ($categoriaFiltro) {
            $catsGruppo = $categories->where('categoria', $categoriaFiltro)->values();
            $catIds     = $catsGruppo->pluck('id');

            // Carico tutte le spese della categoria una volta sola (aggregazione in PHP,
            // evita YEAR()/MONTH() che non esistono in SQLite).
            $tuteLeSpese = BudgetExpense::where('user_id', auth()->id())
                ->whereIn('budget_category_id', $catIds)
                ->get(['data', 'importo']);

            $anni = $tuteLeSpese
                ->map(fn($e) => $e->data?->year)
                ->toBase()
                ->filter()
                ->push(now()->year)
                ->unique()->sort()->values()->toArray();

            $spesePerAnnoMese = $tuteLeSpese
                ->filter(fn($e) => $e->data !== null)
                ->groupBy(fn($e) => $e->data->year)
                ->map(fn($byYear) => $byYear
                    ->groupBy(fn($e) => $e->data->month)
                    ->map(fn($byMonth) => $byMonth->sum('importo'))
                );

            $datiAnni = [];
            foreach ($anni as $a) {
                $budgetMensile = $catsGruppo->sum(fn($c) => $c->importiPerAnno((int) $a)['mensile']);
                $speseDelAnno  = $spesePerAnnoMese[$a] ?? collect();

                $mesiDati = [];
                for ($m = 1; $m <= 12; $m++) {
                    $mesiDati[] = [
                        'budget' => round($budgetMensile, 2),
                        'speso'  => round((float) ($speseDelAnno[$m] ?? 0), 2),
                    ];
                }
                $datiAnni[(int) $a] = $mesiDati;
            }

            $fineMesePrecedente = now()->startOfMonth()->subDay();
            $annoTot            = $fineMesePrecedente->year;
            $meseTot            = $fineMesePrecedente->month;
            $budgetMensileTot   = $catsGruppo->sum(fn($c) => $c->importiPerAnno($annoTot)['mensile']);
            $totaleBudget       = round($budgetMensileTot * $meseTot, 2);
            $totaleSpeso        = round(
                $tuteLeSpese
                    ->filter(fn($e) => $e->data !== null && $e->data->year === $annoTot && $e->data->month <= $meseTot)
                    ->sum('importo'),
                2
            );

            $grafico = [
                'anni'         => $anni,
                'dati'         => $datiAnni,
                'totaleBudget' => $totaleBudget,
                'totaleSpeso'  => $totaleSpeso,
                'alData'        => $fineMesePrecedente->translatedFormat('d F Y'),
                'annoFiltro'    => (int) $anno,
                'annoCorrente'  => now()->year,
                'meseCorrente'  => now()->month,
            ];
        }

        return view('budget.spese', compact('spese', 'totale', 'categories', 'categorie', 'anno', 'mese', 'categoriaFiltro', 'grafico'));
    }

    public function edit(BudgetExpense $expense)
    {
        abort_unless($expense->user_id === auth()->id(), 403);
        $categories = BudgetCategory::orderBy('sort_order')->orderBy('categoria')->get();
        return view('budget.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, BudgetExpense $expense)
    {
        abort_unless($expense->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'importo' => 'required|numeric|min:0.01',
            'data' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        $expense->update($validated);

        return redirect()->route('budget.spese', [
            'anno' => $expense->data->year,
            'mese' => $expense->data->month,
        ])->with('success', 'Spesa aggiornata correttamente.');
    }

    public function destroy(BudgetExpense $expense)
    {
        abort_unless($expense->user_id === auth()->id(), 403);
        $expense->delete();
        return back()->with('success', 'Spesa eliminata.');
    }

    public function categoriesJson()
    {
        return response()->json(
            BudgetCategory::orderBy('sort_order')->orderBy('categoria')->get(['id', 'categoria', 'nome'])
        );
    }
}
