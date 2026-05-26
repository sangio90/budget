<x-app-layout>
<div class="px-4 py-6 max-w-4xl mx-auto space-y-5" x-data="importiApp()">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Budget per Anno</h1>
            <p class="text-sm text-slate-500 mt-0.5">Modifica i budget annuali per categoria. Il mensile viene calcolato automaticamente (annuale ÷ 12).</p>
        </div>
        {{-- Selettore anno --}}
        <div class="flex gap-2">
            @foreach($anni as $a)
                <a href="{{ route('budget.importi', ['anno' => $a]) }}"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition
                        {{ $anno == $a ? 'bg-primary-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:text-primary-600' }}">
                    {{ $a }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Totali --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Totale Annuale {{ $anno }}</div>
            <div class="text-2xl font-bold text-slate-800" x-text="'€ ' + formatNum(totaleAnnuale)">€ {{ number_format($totaleAnnuale, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Totale Mensile {{ $anno }}</div>
            <div class="text-2xl font-bold text-slate-800" x-text="'€ ' + formatNum(totaleMensile)">€ {{ number_format($totaleMensile, 2, ',', '.') }}</div>
        </div>
    </div>

    {{-- Form principale --}}
    <form method="POST" action="{{ route('budget.importi.save') }}" id="formImporti">
        @csrf
        <input type="hidden" name="anno" value="{{ $anno }}">

        <div class="space-y-3">
            @foreach($rows as $catName => $items)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    {{-- Header categoria --}}
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <span class="font-semibold text-slate-700 text-sm">{{ $catName }}</span>
                        @php
                            $totCat = collect($items)->sum('importo_annuale');
                        @endphp
                        <span class="text-xs text-slate-400 font-medium cat-total-{{ \Illuminate\Support\Str::slug($catName) }}">
                            {{ number_format($totCat, 2, ',', '.') }} € / anno
                        </span>
                    </div>

                    {{-- Righe voci --}}
                    <div class="divide-y divide-slate-50">
                        @foreach($items as $item)
                            <div class="px-5 py-3 flex items-center gap-4 flex-wrap sm:flex-nowrap"
                                x-data="{ annuale: {{ $item['importo_annuale'] }}, mensile: {{ $item['importo_mensile'] }} }">

                                {{-- Nome + badge override --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm text-slate-700 font-medium">{{ $item['nome'] }}</span>
                                        @if($item['is_override'])
                                            <span class="text-xs bg-primary-100 text-primary-600 px-1.5 py-0.5 rounded-full font-medium">
                                                personalizzato {{ $anno }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $item['periodo'] }}</div>
                                </div>

                                {{-- Input importo annuale --}}
                                <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
                                    <div class="flex-1 sm:flex-none">
                                        <label class="block text-xs text-slate-400 mb-1">Annuale (€)</label>
                                        <input
                                            type="number"
                                            name="importi[{{ $item['id'] }}]"
                                            step="0.01"
                                            min="0"
                                            x-model="annuale"
                                            @input="mensile = Math.round(annuale / 12 * 100) / 100; recalcTotals()"
                                            class="w-full sm:w-36 border border-slate-200 rounded-lg px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 text-right"
                                            :class="{ 'border-primary-300 bg-primary-50': annuale != {{ $item['default_annuale'] }} }">
                                    </div>
                                    <div class="flex-1 sm:flex-none">
                                        <label class="block text-xs text-slate-400 mb-1">Mensile</label>
                                        <div class="w-full sm:w-28 border border-slate-100 bg-slate-50 rounded-lg px-3 py-2 text-sm text-slate-500 text-right"
                                            x-text="'€ ' + formatNum(mensile)">
                                            € {{ number_format($item['importo_mensile'], 2, ',', '.') }}
                                        </div>
                                    </div>

                                    {{-- Reset al default (solo se override) --}}
                                    @if($item['is_override'])
                                        <form method="POST" action="{{ route('budget.importi.reset', $item['id']) }}" class="self-end"
                                            onsubmit="return confirm('Ripristinare il valore di default per il {{ $anno }}?')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="anno" value="{{ $anno }}">
                                            <button type="submit" title="Ripristina default"
                                                class="text-slate-300 hover:text-amber-500 transition p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Sticky save bar --}}
        <div class="sticky bottom-20 lg:bottom-4 mt-4">
            <div class="bg-slate-900 text-white rounded-2xl px-5 py-4 flex items-center justify-between shadow-xl">
                <div>
                    <div class="text-sm font-semibold">Budget {{ $anno }}</div>
                    <div class="text-xs text-slate-400" x-text="'Annuale: € ' + formatNum(totaleAnnuale) + ' · Mensile: € ' + formatNum(totaleMensile)">
                        Annuale: € {{ number_format($totaleAnnuale, 2, ',', '.') }} · Mensile: € {{ number_format($totaleMensile, 2, ',', '.') }}
                    </div>
                </div>
                <button type="submit"
                    class="bg-primary-600 hover:bg-primary-500 text-white font-semibold px-6 py-2.5 rounded-xl transition text-sm">
                    Salva tutto
                </button>
            </div>
        </div>
    </form>

</div>

<script>
function importiApp() {
    return {
        totaleAnnuale: {{ $totaleAnnuale }},
        totaleMensile: {{ $totaleMensile }},

        formatNum(n) {
            return parseFloat(n).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        recalcTotals() {
            let totA = 0;
            document.querySelectorAll('input[name^="importi["]').forEach(input => {
                totA += parseFloat(input.value) || 0;
            });
            this.totaleAnnuale = totA;
            this.totaleMensile = Math.round(totA / 12 * 100) / 100;
        }
    }
}
</script>
</x-app-layout>
