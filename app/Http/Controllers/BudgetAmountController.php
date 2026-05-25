<?php

namespace App\Http\Controllers;

use App\Models\BudgetAmount;
use App\Models\BudgetCategory;
use Illuminate\Http\Request;

class BudgetAmountController extends Controller
{
    public function index(Request $request)
    {
        $anno = (int) $request->input('anno', now()->year);
        $anni = [2024, 2025, 2026, 2027];

        $categories = BudgetCategory::with(['amounts' => fn($q) => $q->where('anno', $anno)])
            ->orderBy('sort_order')
            ->orderBy('categoria')
            ->get();

        // Prepara dati con importi effettivi per l'anno selezionato
        $rows = $categories->groupBy('categoria')->map(function ($items) use ($anno) {
            return $items->map(function ($cat) use ($anno) {
                $importi = $cat->importiPerAnno($anno);
                $hasOverride = $cat->amounts->isNotEmpty();
                return [
                    'id' => $cat->id,
                    'nome' => $cat->nome,
                    'periodo' => $cat->periodo,
                    'importo_annuale' => $importi['annuale'],
                    'importo_mensile' => $importi['mensile'],
                    'is_override' => $hasOverride,
                    'default_annuale' => $cat->importo_annuale,
                ];
            });
        });

        $totaleAnnuale = $categories->sum(fn($c) => $c->importiPerAnno($anno)['annuale']);
        $totaleMensile = $categories->sum(fn($c) => $c->importiPerAnno($anno)['mensile']);

        return view('budget.importi', compact('rows', 'anno', 'anni', 'totaleAnnuale', 'totaleMensile'));
    }

    public function save(Request $request)
    {
        $anno = (int) $request->input('anno');
        $importi = $request->input('importi', []);

        $request->validate([
            'anno' => 'required|integer|min:2020|max:2030',
            'importi' => 'required|array',
            'importi.*' => 'numeric|min:0',
        ]);

        foreach ($importi as $categoryId => $annuale) {
            $annuale = (float) $annuale;
            $mensile = round($annuale / 12, 2);

            BudgetAmount::updateOrCreate(
                ['budget_category_id' => $categoryId, 'anno' => $anno],
                ['importo_annuale' => $annuale, 'importo_mensile' => $mensile]
            );
        }

        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', "Budget {$anno} salvato correttamente.");
    }

    public function reset(Request $request, BudgetCategory $category)
    {
        $anno = (int) $request->input('anno', now()->year);
        BudgetAmount::where('budget_category_id', $category->id)->where('anno', $anno)->delete();
        return back()->with('success', "Budget ripristinato al valore di default per {$anno}.");
    }
}
