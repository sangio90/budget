<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $anno = $request->input('anno', now()->year);
        $mese = $request->input('mese', '');
        $tipo = $request->input('tipo', '');

        $query = Transaction::where('user_id', auth()->id())
            ->whereYear('data', $anno);

        if ($mese) $query->whereMonth('data', $mese);
        if ($tipo) $query->where('tipo', $tipo);

        $transactions = $query->orderByDesc('data')->orderByDesc('id')->get();

        $saldoTotale = Transaction::where('user_id', auth()->id())
            ->selectRaw("
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) -
                SUM(CASE WHEN tipo IN ('uscita','f24') THEN importo ELSE 0 END) as saldo
            ")->value('saldo') ?? 0;

        $entrateAnno = Transaction::where('user_id', auth()->id())
            ->whereYear('data', $anno)->where('tipo', 'entrata')->sum('importo');
        $usciteAnno = Transaction::where('user_id', auth()->id())
            ->whereYear('data', $anno)->where('tipo', 'uscita')->sum('importo');
        $f24Anno = Transaction::where('user_id', auth()->id())
            ->whereYear('data', $anno)->where('tipo', 'f24')->sum('importo');

        $mensili = Transaction::where('user_id', auth()->id())
            ->whereYear('data', $anno)
            ->selectRaw("MONTH(data) as mese_num,
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
                SUM(CASE WHEN tipo IN ('uscita','f24') THEN importo ELSE 0 END) as uscite")
            ->groupBy('mese_num')
            ->orderBy('mese_num')
            ->get();

        $causaliSuggerite = Transaction::where('user_id', auth()->id())
            ->select('causale', 'tipo')
            ->distinct()
            ->orderBy('causale')
            ->get()
            ->groupBy('tipo')
            ->map(fn($g) => $g->pluck('causale')->unique()->values());

        $fiscale = $this->calcolaFiscale($entrateAnno, $usciteAnno, $f24Anno);

        return view('transactions.index', compact(
            'transactions', 'saldoTotale', 'entrateAnno', 'usciteAnno', 'f24Anno',
            'mensili', 'causaliSuggerite', 'anno', 'mese', 'tipo', 'fiscale'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:entrata,uscita,f24',
            'importo' => 'required|numeric|min:0.01',
            'data' => 'required|date',
            'causale' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);

        Transaction::create(['user_id' => auth()->id()] + $validated);

        return redirect()->route('transactions.index')->with('success', 'Transazione inserita correttamente.');
    }

    public function destroy(Transaction $transaction)
    {
        abort_unless($transaction->user_id === auth()->id(), 403);
        $transaction->delete();
        return back()->with('success', 'Transazione eliminata.');
    }

    private function calcolaFiscale(float $entrate, float $uscite, float $f24): array
    {
        $granTotale  = $entrate - $uscite - $f24;
        $iva         = $entrate * 0.22;
        $imponibile  = $granTotale - $iva;

        $irpef = function (float $base): float {
            if ($base <= 0)      return 0;
            if ($base <= 28000)  return $base * 0.28;
            if ($base <= 50000)  return 28000 * 0.28 + ($base - 28000) * 0.33;
            return 28000 * 0.28 + 22000 * 0.33 + ($base - 50000) * 0.43;
        };

        // Scenario 1: IRPEF piena sull'imponibile
        $irpef1 = $irpef($imponibile);
        $netto1  = $imponibile - $irpef1;

        // Scenario 2: con ritenuta d'acconto 20% già trattenuta alla fonte
        $lordo   = $imponibile > 0 ? $imponibile / 0.8 : 0;
        $irpef2  = max(0, $irpef($lordo) - $lordo * 0.20);
        $netto2  = $imponibile - $irpef2;

        return compact('granTotale', 'iva', 'imponibile', 'irpef1', 'netto1', 'irpef2', 'netto2');
    }

    public function causaliJson(Request $request)
    {
        $tipo = $request->input('tipo');
        $causali = Transaction::where('user_id', auth()->id())
            ->when($tipo, fn($q) => $q->where('tipo', $tipo))
            ->select('causale')->distinct()->orderBy('causale')
            ->pluck('causale');
        return response()->json($causali);
    }
}
