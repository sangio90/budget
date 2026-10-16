<?php

namespace App\Http\Controllers;

use App\Models\ExtraIncome;
use Illuminate\Http\Request;

class ExtraIncomeController extends Controller
{
    public function index(Request $request)
    {
        $anno      = (int) $request->input('anno', now()->year);
        $mese      = $request->input('mese', '');
        $categoria = $request->input('categoria', '');
        $immobile  = $request->input('immobile', '');

        $query = ExtraIncome::where('user_id', auth()->id())
            ->whereYear('data', $anno);

        if ($mese)      $query->whereMonth('data', $mese);
        if ($categoria) $query->where('categoria', $categoria);
        if ($immobile)  $query->where('immobile', $immobile);

        $entrate        = $query->orderByDesc('data')->orderByDesc('id')->get();
        $totaleFiltrato = $entrate->sum('importo');

        $riepilogo = ExtraIncome::riepilogoAnno(auth()->id(), $anno);
        $anni      = $this->anni();

        return view('extra-incomes.index', array_merge(
            compact('entrate', 'totaleFiltrato', 'riepilogo', 'anni', 'anno', 'mese', 'categoria', 'immobile'),
            $this->suggerimenti()
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->regole());

        $entrata = ExtraIncome::create(['user_id' => auth()->id()] + $validated);

        return redirect()->route('extra-incomes.index', ['anno' => $entrata->data->year])
            ->with('success', 'Entrata extra inserita correttamente.');
    }

    public function edit(ExtraIncome $extraIncome)
    {
        abort_unless($extraIncome->user_id === auth()->id(), 403);

        // Lista (con i filtri) da cui si è arrivati, per tornarci dopo il salvataggio.
        // old() conserva il valore quando la validazione fallisce e si ricarica il form.
        $back = $this->urlLista(old('back', url()->previous()));

        return view('extra-incomes.edit', array_merge(
            ['entrata' => $extraIncome, 'back' => $back],
            $this->suggerimenti()
        ));
    }

    public function update(Request $request, ExtraIncome $extraIncome)
    {
        abort_unless($extraIncome->user_id === auth()->id(), 403);

        $extraIncome->update($request->validate($this->regole()));

        $back = $this->urlLista($request->input('back'))
            ?? route('extra-incomes.index', ['anno' => $extraIncome->data->year]);

        return redirect()->to($back)->with('success', 'Entrata extra aggiornata correttamente.');
    }

    public function destroy(ExtraIncome $extraIncome)
    {
        abort_unless($extraIncome->user_id === auth()->id(), 403);
        $extraIncome->delete();
        return back()->with('success', 'Entrata extra eliminata.');
    }

    private function regole(): array
    {
        return [
            'categoria' => 'required|string|max:255',
            'immobile'  => 'nullable|string|max:255',
            // Ammessi importi negativi (es. ritenute/spese di liquidazione sugli interessi)
            'importo'   => 'required|numeric|not_in:0|between:-99999999.99,99999999.99',
            'data'      => 'required|date',
            'causale'   => 'required|string|max:255',
            'note'      => 'nullable|string|max:500',
        ];
    }

    /** Valori suggeriti per categoria, immobile e causale (fissi + già usati dall'utente). */
    private function suggerimenti(): array
    {
        $usati = fn(string $col) => ExtraIncome::where('user_id', auth()->id())
            ->whereNotNull($col)->where($col, '!=', '')
            ->distinct()->orderBy($col)->pluck($col);

        return [
            'categorie' => collect(ExtraIncome::CATEGORIE)->concat($usati('categoria'))->unique()->values(),
            'immobili'  => collect(ExtraIncome::IMMOBILI)->concat($usati('immobile'))->unique()->sort()->values(),
            'causali'   => $usati('causale'),
        ];
    }

    /** Anni selezionabili: dal primo anno con entrate extra (o 2025) all'anno in corso. */
    private function anni(): array
    {
        $primaData = ExtraIncome::where('user_id', auth()->id())->min('data');
        $inizio    = min(2025, $primaData ? (int) substr($primaData, 0, 4) : now()->year);

        return range(max(now()->year, $inizio), $inizio);
    }

    /**
     * Restituisce l'URL solo se punta alla lista Entrate extra di questa app (evita open redirect).
     */
    private function urlLista(?string $url): ?string
    {
        $base = route('extra-incomes.index');

        return $url && ($url === $base || str_starts_with($url, $base . '?')) ? $url : null;
    }
}
