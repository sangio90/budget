{{-- Parametri fiscali dell'anno --}}
@php
    $in  = 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white';
    $num = fn($campo, $perc = false) => old($campo, $parametri->getAttribute($campo) === null ? '' : ($perc ? round((float) $parametri->getAttribute($campo) * 100, 4) : (float) $parametri->getAttribute($campo)));
    $scTesto = fn(array $sc) => collect($sc)->map(fn($s) => ($s['fino'] === null ? '*' : (float) $s['fino']) . ':' . round($s['aliquota'] * 100, 4))->implode('; ');
    $gruppi = [
        'Aliquote' => [
            ['aliquota_inps', 'INPS gestione separata (%)', true], ['massimale_inps', 'Massimale INPS (€)', false],
            ['aliquota_iva', 'IVA (%)', true], ['aliquota_ritenuta', 'Ritenuta d\'acconto (%)', true],
            ['aliquota_add_comunale', 'Addizionale comunale (%)', true], ['accantonamento_perc', 'Accantonamento obiettivo (% incassato)', true],
        ],
        'Altri redditi, deduzioni e detrazioni' => [
            ['redditi_fabbricati', 'Redditi fabbricati tassati IRPEF (€)', false], ['cedolare_secca', 'Cedolare secca (€)', false],
            ['fondo_pensione', 'Fondo pensione deducibile (€)', false], ['detrazioni', 'Detrazioni d\'imposta (€)', false],
        ],
        'Dichiarazione anno precedente' => [
            ['prec_inps_dovuto', 'INPS dovuto (€)', false], ['prec_inps_acconti', 'Acconti INPS versati (€)', false],
            ['prec_irpef_netta', 'IRPEF netta (€)', false], ['prec_ritenute', 'Ritenute (€)', false],
            ['prec_cedolare', 'Cedolare secca (€)', false], ['iva_base_acconto', 'Base acconto IVA storico (€)', false],
        ],
        'Crediti' => [
            ['credito_irpef_iniziale', 'Credito IRPEF disponibile (€)', false], ['credito_iva_iniziale', 'Credito IVA disponibile (€)', false],
        ],
        'Ipotesi di proiezione (vuoto = calcolate dai movimenti)' => [
            ['compensi_previsti', 'Compensi annui imponibili (€)', false], ['compensi_ra_previsti', 'di cui soggetti a ritenuta (€)', false],
            ['costi_previsti', 'Costi attività (€)', false], ['affitti_previsti', 'Affitti incassati (€)', false],
            ['iva_annua_prevista', 'IVA annua da versare (€)', false], ['spese_famiglia_previste', 'Spese famiglia annue (€)', false],
        ],
    ];
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ aperto: {{ $errors->hasAny(array_merge(\App\Models\FiscalYear::CAMPI_NUMERICI, ['scaglioni_irpef', 'scaglioni_add_regionale'])) ? 'true' : 'false' }} }">
    <button type="button" @click="aperto = !aperto" class="w-full px-5 py-4 flex items-center justify-between text-left">
        <div>
            <h2 class="font-semibold text-slate-800">Parametri {{ $anno }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">
                {{ $parametri->exists ? 'Salvati' : 'Non ancora salvati: valori ereditati dall\'anno precedente' }}
                · detrazioni {{ $eu($parametri->detrazioni) }} · INPS {{ $pc($parametri->aliquota_inps, 2) }}
            </p>
        </div>
        <svg class="w-5 h-5 text-slate-400 transition" :class="aperto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <form x-show="aperto" x-cloak method="POST" action="{{ route('fiscale.parametri.update', $anno) }}" class="px-5 pb-5 space-y-5 border-t border-slate-100 pt-4">
        @csrf @method('PUT')
        @foreach($gruppi as $titolo => $campi)
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ $titolo }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach($campi as [$campo, $label, $perc])
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">{{ $label }}</label>
                            <input type="number" step="any" name="{{ $campo }}" value="{{ $num($campo, $perc) }}" class="{{ $in }}">
                            @error($campo)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Scaglioni IRPEF (fino:aliquota%)</label>
                <input type="text" name="scaglioni_irpef" value="{{ old('scaglioni_irpef', $scTesto($parametri->scaglioniIrpef())) }}" class="{{ $in }}">
                @error('scaglioni_irpef')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Scaglioni addizionale regionale (fino:aliquota%)</label>
                <input type="text" name="scaglioni_add_regionale" value="{{ old('scaglioni_add_regionale', $scTesto($parametri->scaglioniAddRegionale())) }}" class="{{ $in }}">
                @error('scaglioni_add_regionale')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Note</label>
            <textarea name="note" rows="2" class="{{ $in }}">{{ old('note', $parametri->note) }}</textarea>
        </div>
        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition text-sm">Salva parametri {{ $anno }}</button>
    </form>
</div>
