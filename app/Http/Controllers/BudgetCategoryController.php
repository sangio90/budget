<?php

namespace App\Http\Controllers;

use App\Models\BudgetCategory;
use Illuminate\Http\Request;

class BudgetCategoryController extends Controller
{
    public function storeVoce(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string|max:100',
            'nome' => 'required|string|max:200',
            'importo_annuale' => 'required|numeric|min:0',
            'periodo' => 'nullable|string|max:100',
        ]);

        $categoria = strtoupper(trim($request->categoria));
        $mensile = round((float) $request->importo_annuale / 12, 2);
        $maxSort = BudgetCategory::where('categoria', $categoria)->max('sort_order') ?? 0;

        BudgetCategory::create([
            'categoria' => $categoria,
            'nome' => trim($request->nome),
            'importo_annuale' => (float) $request->importo_annuale,
            'importo_mensile' => $mensile,
            'periodo' => $request->input('periodo', 'Mensile'),
            'sort_order' => $maxSort + 1,
        ]);

        $anno = (int) $request->input('anno', now()->year);
        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', 'Voce di spesa aggiunta.');
    }

    public function updateVoce(Request $request, BudgetCategory $category)
    {
        $request->validate([
            'categoria' => 'required|string|max:100',
            'nome' => 'required|string|max:200',
            'importo_annuale' => 'required|numeric|min:0',
            'periodo' => 'nullable|string|max:100',
        ]);

        $mensile = round((float) $request->importo_annuale / 12, 2);

        $category->update([
            'categoria' => strtoupper(trim($request->categoria)),
            'nome' => trim($request->nome),
            'importo_annuale' => (float) $request->importo_annuale,
            'importo_mensile' => $mensile,
            'periodo' => $request->input('periodo', 'Mensile'),
        ]);

        $anno = (int) $request->input('anno', now()->year);
        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', 'Voce di spesa aggiornata.');
    }

    public function destroyVoce(Request $request, BudgetCategory $category)
    {
        $category->amounts()->delete();
        $category->delete();

        $anno = (int) $request->input('anno', now()->year);
        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', 'Voce di spesa eliminata.');
    }

    public function renameCategoria(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string|max:100',
            'new_name' => 'required|string|max:100',
        ]);

        $newName = strtoupper(trim($request->new_name));
        BudgetCategory::where('categoria', $request->old_name)
            ->update(['categoria' => $newName]);

        $anno = (int) $request->input('anno', now()->year);
        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', "Categoria rinominata in \"{$newName}\".");
    }

    public function destroyCategoria(Request $request)
    {
        $request->validate([
            'categoria' => 'required|string|max:100',
        ]);

        $categories = BudgetCategory::where('categoria', $request->categoria)->get();
        foreach ($categories as $cat) {
            $cat->amounts()->delete();
            $cat->delete();
        }

        $anno = (int) $request->input('anno', now()->year);
        return redirect()->route('budget.importi', ['anno' => $anno])
            ->with('success', "Categoria \"{$request->categoria}\" eliminata.");
    }
}
