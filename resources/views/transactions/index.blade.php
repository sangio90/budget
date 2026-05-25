<x-app-layout>
<div class="px-4 py-6 max-w-7xl mx-auto space-y-6" x-data="txApp()">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Entrate / Uscite</h1>
            <p class="text-sm text-slate-500 mt-0.5">Stipendi, F24 e incassi</p>
        </div>
    </div>

    {{-- Saldo e KPI --}}
    @php
        $deltaAnno = $entrateAnno - ($usciteAnno + $f24Anno);
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4">
        <div class="col-span-2 sm:col-span-3 xl:col-span-1 bg-slate-900 text-white rounded-2xl p-5">
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Saldo Complessivo</div>
            <div class="text-3xl font-bold {{ $saldoTotale >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ number_format($saldoTotale, 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-500 mt-1">tutte le entrate - tutte le uscite</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Entrate {{ $anno }}</div>
            <div class="text-2xl font-bold text-emerald-600">{{ number_format($entrateAnno, 2, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Uscite {{ $anno }}</div>
            <div class="text-2xl font-bold text-red-500">{{ number_format($usciteAnno, 2, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">F24 {{ $anno }}</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($f24Anno, 2, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Delta {{ $anno }}</div>
            <div class="text-2xl font-bold {{ $deltaAnno >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ ($deltaAnno >= 0 ? '+' : '') . number_format($deltaAnno, 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-400 mt-1">entrate − (uscite + F24)</div>
        </div>
    </div>

    {{-- Riepilogo Fiscale --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Riepilogo Fiscale {{ $anno }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">Basato sulle entrate/uscite/F24 dell'anno · IVA 22% · scaglioni IRPEF vigenti</p>
        </div>
        <div class="px-5 py-4 space-y-4">

            {{-- Calcolo base --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div class="bg-slate-50 rounded-xl p-3">
                    <div class="text-xs text-slate-400 mb-0.5">Entrate</div>
                    <div class="font-semibold text-slate-700">{{ number_format($entrateAnno, 2, ',', '.') }} €</div>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <div class="text-xs text-slate-400 mb-0.5">− Stipendi</div>
                    <div class="font-semibold text-red-500">{{ number_format($usciteAnno, 2, ',', '.') }} €</div>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <div class="text-xs text-slate-400 mb-0.5">− F24 pagati</div>
                    <div class="font-semibold text-amber-600">{{ number_format($f24Anno, 2, ',', '.') }} €</div>
                </div>
                <div class="bg-indigo-50 rounded-xl p-3">
                    <div class="text-xs text-indigo-400 mb-0.5">Gran totale</div>
                    <div class="font-bold text-indigo-700">{{ number_format($fiscale['granTotale'], 2, ',', '.') }} €</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-slate-50 rounded-xl p-3">
                    <div class="text-xs text-slate-400 mb-0.5">− IVA da pagare (22% entrate)</div>
                    <div class="font-semibold text-red-500">{{ number_format($fiscale['iva'], 2, ',', '.') }} €</div>
                </div>
                <div class="bg-amber-50 rounded-xl p-3">
                    <div class="text-xs text-amber-600 mb-0.5">Imponibile IRPEF</div>
                    <div class="font-bold text-amber-700">{{ number_format($fiscale['imponibile'], 2, ',', '.') }} €</div>
                    <div class="text-xs text-slate-400 mt-0.5">gran totale − IVA</div>
                </div>
            </div>

            {{-- Due scenari IRPEF --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Scenario 1: senza ritenuta --}}
                <div class="border border-slate-200 rounded-xl p-4 space-y-2">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Scenario A · senza ritenuta d'acconto</div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">IRPEF dovuta</span>
                        <span class="font-semibold text-red-500">{{ number_format($fiscale['irpef1'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="border-t border-slate-100 pt-2 flex justify-between text-sm">
                        <span class="text-slate-600 font-medium">Netto stimato</span>
                        <span class="font-bold {{ $fiscale['netto1'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($fiscale['netto1'], 2, ',', '.') }} €</span>
                    </div>
                </div>

                {{-- Scenario 2: con ritenuta 20% --}}
                <div class="border border-emerald-200 bg-emerald-50/30 rounded-xl p-4 space-y-2">
                    <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Scenario B · con ritenuta d'acconto 20%</div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">IRPEF residua da versare</span>
                        <span class="font-semibold text-red-500">{{ number_format($fiscale['irpef2'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="border-t border-emerald-100 pt-2 flex justify-between text-sm">
                        <span class="text-slate-600 font-medium">Netto vero</span>
                        <span class="font-bold {{ $fiscale['netto2'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($fiscale['netto2'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="text-xs text-slate-400">Il 20% di ritenuta d'acconto è già stato trattenuto alla fonte</div>
                </div>
            </div>

            <p class="text-xs text-slate-400">* Stima indicativa. Deduci spese deducibili (ufficio, software, ecc.) dall'imponibile per il calcolo definitivo.</p>
        </div>
    </div>

    {{-- Riepilogo mensile --}}
    @if($mensili->count())
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Riepilogo Mensile {{ $anno }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Mese</th>
                        <th class="px-5 py-3 text-right">Entrate</th>
                        <th class="px-5 py-3 text-right">Uscite</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($mensili as $row)
                        @php
                            $saldoM = $row->entrate - $row->uscite;
                            $mesi = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $mesi[(int)$row->mese_num] }}</td>
                            <td class="px-5 py-3 text-right text-emerald-600 font-medium">{{ number_format($row->entrate, 2, ',', '.') }} €</td>
                            <td class="px-5 py-3 text-right text-red-500 font-medium">{{ number_format($row->uscite, 2, ',', '.') }} €</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $saldoM >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($saldoM, 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Form inserimento --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Inserisci Movimento</h2>
        </div>
        <form method="POST" action="{{ route('transactions.store') }}" class="px-5 py-4 space-y-4" @submit="onSubmit">
            @csrf

            {{-- Tipo con toggle visivo --}}
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Tipo</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['entrata' => ['label' => 'Entrata', 'color' => 'peer-checked:bg-emerald-500 peer-checked:border-emerald-500 peer-checked:text-white'], 'uscita' => ['label' => 'Uscita', 'color' => 'peer-checked:bg-red-500 peer-checked:border-red-500 peer-checked:text-white'], 'f24' => ['label' => 'F24', 'color' => 'peer-checked:bg-amber-500 peer-checked:border-amber-500 peer-checked:text-white']] as $val => $cfg)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="tipo" value="{{ $val }}" class="peer sr-only" x-model="tipo" @change="loadCausali()" {{ old('tipo', 'uscita') === $val ? 'checked' : '' }}>
                            <div class="border-2 border-slate-200 rounded-xl py-3 text-center text-sm font-medium text-slate-600 transition {{ $cfg['color'] }}">
                                {{ $cfg['label'] }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('tipo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Importo + Data --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo (€)</label>
                    <input type="number" name="importo" step="0.01" min="0.01" required placeholder="0,00"
                        value="{{ old('importo') }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('importo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Data</label>
                    <input type="date" name="data" required value="{{ old('data', now()->format('Y-m-d')) }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('data')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Causale con autocomplete --}}
            <div class="relative">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Causale</label>
                <input type="text" name="causale" required placeholder="Es. Emanuele, ILT, iva 1 trimestre..."
                    value="{{ old('causale') }}"
                    x-model="causale"
                    @input.debounce.200ms="filterSuggest()"
                    @focus="filterSuggest()"
                    @keydown.arrow-down.prevent="selectNext()"
                    @keydown.arrow-up.prevent="selectPrev()"
                    @keydown.enter.prevent="confirmSuggest()"
                    @keydown.escape="showSuggest = false"
                    autocomplete="off"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('causale')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

                {{-- Dropdown suggerimenti --}}
                <div x-show="showSuggest && filteredCausali.length > 0"
                    @click.outside="showSuggest = false"
                    class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-48 overflow-y-auto">
                    <template x-for="(s, idx) in filteredCausali" :key="s">
                        <button type="button"
                            @click="selectCausale(s)"
                            :class="idx === activeIdx ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50'"
                            class="w-full text-left px-4 py-2.5 text-sm border-b border-slate-50 last:border-0 transition">
                            <span x-text="s"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Note --}}
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
                <input type="text" name="note" placeholder="Note aggiuntive..." value="{{ old('note') }}"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Registra Movimento
            </button>
        </form>
    </div>

    {{-- Filtri lista --}}
    <div class="flex gap-2 flex-wrap">
        <form method="GET" action="{{ route('transactions.index') }}" class="flex gap-2 flex-wrap">
            <select name="anno" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach([2024, 2025, 2026] as $a)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
            <select name="mese" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Tutti i mesi</option>
                @foreach(range(1,12) as $m)
                    @php $nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre']; @endphp
                    <option value="{{ $m }}" {{ $mese == $m ? 'selected' : '' }}>{{ $nomiMesi[$m] }}</option>
                @endforeach
            </select>
            <select name="tipo" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Tutti i tipi</option>
                <option value="entrata" {{ $tipo == 'entrata' ? 'selected' : '' }}>Entrate</option>
                <option value="uscita" {{ $tipo == 'uscita' ? 'selected' : '' }}>Uscite</option>
                <option value="f24" {{ $tipo == 'f24' ? 'selected' : '' }}>F24</option>
            </select>
        </form>
    </div>

    {{-- Lista movimenti --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Movimenti ({{ $transactions->count() }})</h2>
        </div>

        @if($transactions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm hidden sm:table">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">Tipo</th>
                        <th class="px-5 py-3 text-left">Causale</th>
                        <th class="px-5 py-3 text-right">Importo</th>
                        <th class="px-5 py-3 text-left">Note</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($transactions as $t)
                        @php
                            $sign = $t->tipo === 'entrata' ? '+' : '-';
                            $amtColor = $t->tipo === 'entrata' ? 'text-emerald-600' : 'text-red-500';
                            $badge = match($t->tipo) { 'entrata' => 'bg-emerald-100 text-emerald-700', 'f24' => 'bg-amber-100 text-amber-700', default => 'bg-red-100 text-red-700' };
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3 text-slate-500">{{ $t->data->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $badge }}">{{ strtoupper($t->tipo) }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $t->causale }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $amtColor }}">{{ $sign }}{{ number_format($t->importo, 2, ',', '.') }} €</td>
                            <td class="px-5 py-3 text-slate-400 text-xs max-w-xs truncate">{{ $t->note }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('transactions.destroy', $t) }}" onsubmit="return confirm('Eliminare questo movimento?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-400 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Mobile card list --}}
            <div class="sm:hidden divide-y divide-slate-50">
                @foreach($transactions as $t)
                    @php
                        $sign = $t->tipo === 'entrata' ? '+' : '-';
                        $amtColor = $t->tipo === 'entrata' ? 'text-emerald-600' : 'text-red-500';
                        $badge = match($t->tipo) { 'entrata' => 'bg-emerald-100 text-emerald-700', 'f24' => 'bg-amber-100 text-amber-700', default => 'bg-red-100 text-red-700' };
                    @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-700 text-sm truncate">{{ $t->causale }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $badge }} flex-shrink-0">{{ strtoupper($t->tipo) }}</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $t->data->format('d/m/Y') }}{{ $t->note ? ' · ' . $t->note : '' }}</div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="font-semibold text-sm {{ $amtColor }}">{{ $sign }}{{ number_format($t->importo, 2, ',', '.') }} €</span>
                            <form method="POST" action="{{ route('transactions.destroy', $t) }}" onsubmit="return confirm('Eliminare?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-300 hover:text-red-400 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @else
            <div class="px-5 py-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm">Nessun movimento trovato</p>
            </div>
        @endif
    </div>

</div>

<script>
function txApp() {
    return {
        tipo: '{{ old('tipo', 'uscita') }}',
        causale: '{{ old('causale') }}',
        allCausali: @json($causaliSuggerite),
        filteredCausali: [],
        showSuggest: false,
        activeIdx: -1,

        loadCausali() {
            // causali are already loaded from server per tipo
        },

        filterSuggest() {
            const pool = this.allCausali[this.tipo] ?? [];
            const q = this.causale.toLowerCase();
            this.filteredCausali = q.length === 0
                ? pool.slice(0, 10)
                : pool.filter(c => c.toLowerCase().includes(q)).slice(0, 10);
            this.showSuggest = true;
            this.activeIdx = -1;
        },

        selectCausale(s) {
            this.causale = s;
            this.showSuggest = false;
        },

        selectNext() {
            if (this.activeIdx < this.filteredCausali.length - 1) this.activeIdx++;
        },

        selectPrev() {
            if (this.activeIdx > 0) this.activeIdx--;
        },

        confirmSuggest() {
            if (this.activeIdx >= 0 && this.filteredCausali[this.activeIdx]) {
                this.selectCausale(this.filteredCausali[this.activeIdx]);
            } else {
                this.showSuggest = false;
            }
        },

        onSubmit() {
            this.showSuggest = false;
        }
    }
}
</script>
</x-app-layout>
