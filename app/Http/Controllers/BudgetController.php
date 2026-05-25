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

        $categories = BudgetCategory::orderBy('sort_order')->orderBy('categoria')->get();
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

        return view('budget.spese', compact('spese', 'totale', 'categories', 'categorie', 'anno', 'mese', 'categoriaFiltro'));
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
