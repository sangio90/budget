<x-app-layout>
<div class="px-4 py-6 max-w-4xl mx-auto space-y-5" x-data="importiApp()">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Budget per Anno</h1>
            <p class="text-sm text-slate-500 mt-0.5">Modifica i budget annuali per categoria. Il mensile viene calcolato automaticamente (annuale ÷ 12).</p>
        </div>
        {{-- Selettore anno --}}
        <div class="flex gap-2 flex-wrap">
            @foreach($anni as $a)
                <a href="{{ route('budget.importi', ['anno' => $a]) }}"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition
                        {{ $anno == $a ? 'bg-primary-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-primary-300 hover:text-primary-600' }}">
                    {{ $a }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
        {{ $errors->first() }}
    </div>
    @endif

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

    {{-- Pulsante nuova voce globale --}}
    <div class="flex justify-end">
        <button type="button" @click="openNuovaVoce('')"
            class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuova Voce di Spesa
        </button>
    </div>

    {{-- Form principale --}}
    <form method="POST" action="{{ route('budget.importi.save') }}" id="formImporti">
        @csrf
        <input type="hidden" name="anno" value="{{ $anno }}">

        <div class="space-y-3">
            @foreach($rows as $catName => $items)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    {{-- Header categoria --}}
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between gap-2">
                        <span class="font-semibold text-slate-700 text-sm">{{ $catName }}</span>
                        <div class="flex items-center gap-2">
                            @php $totCat = collect($items)->sum('importo_annuale'); @endphp
                            <span class="text-xs text-slate-400 font-medium hidden sm:inline">
                                {{ number_format($totCat, 2, ',', '.') }} € / anno
                            </span>
                            <button type="button"
                                @click="openNuovaVoce('{{ $catName }}')"
                                title="Aggiungi voce"
                                class="text-slate-400 hover:text-primary-600 transition p-1 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            </button>
                            <button type="button"
                                @click="openRinomina('{{ $catName }}')"
                                title="Rinomina categoria"
                                class="text-slate-400 hover:text-amber-500 transition p-1 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button type="button"
                                @click="confirmDeleteCategoria('{{ $catName }}', {{ count($items) }})"
                                title="Elimina categoria"
                                class="text-slate-400 hover:text-red-500 transition p-1 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
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

                                    {{-- Modifica / Elimina voce --}}
                                    <div class="flex items-center self-end">
                                        <button type="button"
                                            @click="openModificaVoce({{ $item['id'] }}, '{{ addslashes($catName) }}', {{ json_encode($item['nome']) }}, {{ $item['default_annuale'] }}, {{ json_encode($item['periodo'] ?? 'Mensile') }})"
                                            title="Modifica voce"
                                            class="text-slate-300 hover:text-primary-500 transition p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button type="button"
                                            @click="confirmDeleteVoce({{ $item['id'] }}, {{ json_encode($item['nome']) }})"
                                            title="Elimina voce"
                                            class="text-slate-300 hover:text-red-500 transition p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
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

    {{-- Form nascosti per eliminazione (fuori dal form principale) --}}
    <form x-ref="deleteVoceForm" method="POST" :action="_voceDeleteUrl" style="display:none">
        @csrf
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="anno" value="{{ $anno }}">
    </form>

    <form x-ref="deleteCatForm" method="POST" action="{{ route('budget.categorie.destroy') }}" style="display:none">
        @csrf
        <input type="hidden" name="anno" value="{{ $anno }}">
        <input type="hidden" name="categoria" :value="_catDeleteName">
    </form>

    {{-- Modal: Crea / Modifica Voce --}}
    <div x-show="showModalVoce" style="display:none"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="closeModalVoce()">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md"
            @click.stop>
            <h2 class="text-lg font-bold text-slate-800 mb-5"
                x-text="voceEditId ? 'Modifica Voce di Spesa' : 'Nuova Voce di Spesa'"></h2>

            <form :action="voceModalAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="voceEditId ? 'PUT' : 'POST'">
                <input type="hidden" name="anno" value="{{ $anno }}">

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Categoria</label>
                    <input type="text" name="categoria" x-model="voceForm.categoria"
                        list="categorieDatalist" required
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        placeholder="Es. CASA">
                    <datalist id="categorieDatalist">
                        <template x-for="cat in categorieList" :key="cat">
                            <option :value="cat"></option>
                        </template>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Nome Voce</label>
                    <input type="text" name="nome" x-model="voceForm.nome" required
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        placeholder="Es. Bollette Luce">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo Annuale (€)</label>
                        <input type="number" name="importo_annuale" x-model="voceForm.importo_annuale"
                            step="0.01" min="0" required
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Periodo</label>
                        <input type="text" name="periodo" x-model="voceForm.periodo"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Mensile">
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeModalVoce()"
                        class="flex-1 border border-slate-200 text-slate-600 font-semibold py-2.5 rounded-xl transition text-sm hover:bg-slate-50">
                        Annulla
                    </button>
                    <button type="submit"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-xl transition text-sm">
                        Salva
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Rinomina Categoria --}}
    <div x-show="showModalRinomina" style="display:none"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        @click.self="closeModalRinomina()">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm"
            @click.stop>
            <h2 class="text-lg font-bold text-slate-800 mb-1">Rinomina Categoria</h2>
            <p class="text-xs text-slate-400 mb-5">Aggiorna il nome per tutte le voci di questa categoria.</p>

            <form action="{{ route('budget.categorie.rename') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="anno" value="{{ $anno }}">
                <input type="hidden" name="old_name" :value="rinominaOldName">

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Nuovo Nome</label>
                    <input type="text" name="new_name" x-model="rinominaNuovoNome" required
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="closeModalRinomina()"
                        class="flex-1 border border-slate-200 text-slate-600 font-semibold py-2.5 rounded-xl transition text-sm hover:bg-slate-50">
                        Annulla
                    </button>
                    <button type="submit"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-xl transition text-sm">
                        Rinomina
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function importiApp() {
    return {
        totaleAnnuale: {{ $totaleAnnuale }},
        totaleMensile: {{ $totaleMensile }},

        // CRUD state
        showModalVoce: false,
        voceEditId: null,
        voceForm: { categoria: '', nome: '', importo_annuale: 0, periodo: 'Mensile' },
        _voceDeleteUrl: '',
        _catDeleteName: '',
        showModalRinomina: false,
        rinominaOldName: '',
        rinominaNuovoNome: '',
        categorieList: @json($rows->keys()->values()),

        get voceModalAction() {
            return this.voceEditId
                ? `{{ url('budget/categorie/voce') }}/${this.voceEditId}`
                : `{{ route('budget.categorie.voce.store') }}`;
        },

        openNuovaVoce(cat) {
            this.voceEditId = null;
            this.voceForm = { categoria: cat, nome: '', importo_annuale: 0, periodo: 'Mensile' };
            this.showModalVoce = true;
        },

        openModificaVoce(id, cat, nome, importoAnnuale, periodo) {
            this.voceEditId = id;
            this.voceForm = { categoria: cat, nome: nome, importo_annuale: importoAnnuale, periodo: periodo };
            this.showModalVoce = true;
        },

        closeModalVoce() { this.showModalVoce = false; this.voceEditId = null; },

        confirmDeleteVoce(id, nome) {
            if (!confirm(`Eliminare la voce "${nome}"?`)) return;
            this._voceDeleteUrl = `{{ url('budget/categorie/voce') }}/${id}`;
            this.$nextTick(() => this.$refs.deleteVoceForm.submit());
        },

        openRinomina(nome) {
            this.rinominaOldName = nome;
            this.rinominaNuovoNome = nome;
            this.showModalRinomina = true;
        },

        closeModalRinomina() { this.showModalRinomina = false; },

        confirmDeleteCategoria(nome, count) {
            if (!confirm(`Eliminare la categoria "${nome}" e tutte le ${count} voci di spesa?`)) return;
            this._catDeleteName = nome;
            this.$nextTick(() => this.$refs.deleteCatForm.submit());
        },

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
