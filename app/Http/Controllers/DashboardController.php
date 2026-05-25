<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $anno = now()->year;
        $mese = now()->month;

        $categories = \App\Models\BudgetCategory::with('amounts')
            ->orderBy('sort_order')
            ->orderBy('categoria')
            ->get();

        // Spese del mese e dell'anno per categoria, in due query
        $spesePerCategoriaMese = \App\Models\BudgetExpense::where('user_id', auth()->id())
            ->whereYear('data', $anno)->whereMonth('data', $mese)
            ->selectRaw('budget_category_id, SUM(importo) as totale')
            ->groupBy('budget_category_id')
            ->pluck('totale', 'budget_category_id');

        $spesePerCategoriaAnno = \App\Models\BudgetExpense::where('user_id', auth()->id())
            ->whereYear('data', $anno)
            ->selectRaw('budget_category_id, SUM(importo) as totale')
            ->groupBy('budget_category_id')
            ->pluck('totale', 'budget_category_id');

        $righe = $categories->groupBy('categoria')->map(function ($items) use ($anno, $spesePerCategoriaMese, $spesePerCategoriaAnno) {
            $importi       = ['mensile' => $items->sum(fn($c) => $c->importiPerAnno($anno)['mensile']),
                              'annuale' => $items->sum(fn($c) => $c->importiPerAnno($anno)['annuale'])];
            $spesoMese     = $items->sum(fn($c) => (float) ($spesePerCategoriaMese[$c->id] ?? 0));
            $spesoAnno     = $items->sum(fn($c) => (float) ($spesePerCategoriaAnno[$c->id] ?? 0));
            $percMese      = $importi['mensile'] > 0 ? min(100, round($spesoMese / $importi['mensile'] * 100)) : 0;
            $percAnno      = $importi['annuale'] > 0 ? min(100, round($spesoAnno / $importi['annuale'] * 100)) : 0;
            return [
                'budgetMensile' => $importi['mensile'],
                'budgetAnnuale' => $importi['annuale'],
                'speso'         => $spesoMese,
                'spesoAnno'     => $spesoAnno,
                'perc'          => $percMese,
                'percAnno'      => $percAnno,
            ];
        });

        return view('dashboard', compact('righe', 'anno', 'mese'));
    }
}
