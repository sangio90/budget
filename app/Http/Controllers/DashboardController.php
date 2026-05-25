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

        // Spese del mese per categoria, in una sola query
        $spesePerCategoria = \App\Models\BudgetExpense::where('user_id', auth()->id())
            ->whereYear('data', $anno)
            ->whereMonth('data', $mese)
            ->selectRaw('budget_category_id, SUM(importo) as totale')
            ->groupBy('budget_category_id')
            ->pluck('totale', 'budget_category_id');

        $righe = $categories->groupBy('categoria')->map(function ($items) use ($anno, $spesePerCategoria) {
            $budgetMensile = $items->sum(fn($c) => $c->importiPerAnno($anno)['mensile']);
            $speso = $items->sum(fn($c) => (float) ($spesePerCategoria[$c->id] ?? 0));
            $perc = $budgetMensile > 0 ? min(100, round($speso / $budgetMensile * 100)) : 0;
            return compact('budgetMensile', 'speso', 'perc');
        });

        return view('dashboard', compact('righe', 'anno', 'mese'));
    }
}
