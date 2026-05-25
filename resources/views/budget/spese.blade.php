<x-app-layout>
<div class="px-4 py-6 max-w-7xl mx-auto space-y-5">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Spese Budget</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $spese->count() }} voci · totale {{ number_format($totale, 2, ',', '.') }} €</p>
        </div>
        <a href="{{ route('budget.index') }}"
            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuova spesa
        </a>
    </div>

    {{-- Filtri --}}
    <form method="GET" action="{{ route('budget.spese') }}" class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Anno</label>
            <select name="anno" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 pr-8 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach([2024, 2025, 2026] as $a)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Mese</label>
            <select name="mese" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 pr-8 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-[9rem]">
                <option value="">Tutti</option>
                @foreach(range(1,12) as $m)
                    @php $nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre']; @endphp
                    <option value="{{ $m }}" {{ $mese == $m ? 'selected' : '' }}>{{ $nomiMesi[$m] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
            <select name="categoria" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 pr-8 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-[9rem]">
                <option value="">Tutte</option>
                @foreach($categorie as $cat)
                    <option value="{{ $cat }}" {{ $categoriaFiltro == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        @if($categoriaFiltro || $mese != now()->month || $anno != now()->year)
            <a href="{{ route('budget.spese') }}" class="text-sm text-slate-400 hover:text-slate-600 py-2">Azzera</a>
        @endif
    </form>

    {{-- Lista --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        @if($spese->count())

        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">Categoria</th>
                        <th class="px-5 py-3 text-left">Voce</th>
                        <th class="px-5 py-3 text-right">Importo</th>
                        <th class="px-5 py-3 text-left">Note</th>
                        <th class="px-5 py-3 text-right">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($spese as $s)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ $s->data->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium">{{ $s->category->categoria }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $s->category->nome }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-red-500">{{ number_format($s->importo, 2, ',', '.') }} €</td>
                            <td class="px-5 py-3 text-slate-400 text-xs max-w-xs truncate">{{ $s->note }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('budget.edit', $s) }}"
                                        class="text-slate-400 hover:text-indigo-600 transition" title="Modifica">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('budget.destroy', $s) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Eliminare questa spesa?')" class="text-slate-300 hover:text-red-400 transition" title="Elimina">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-100">
                        <td colspan="3" class="px-5 py-3 text-sm font-semibold text-slate-600">Totale</td>
                        <td class="px-5 py-3 text-right font-bold text-red-500">{{ number_format($totale, 2, ',', '.') }} €</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Mobile card list --}}
        <div class="sm:hidden divide-y divide-slate-50">
            @foreach($spese as $s)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-medium text-slate-700 text-sm">{{ $s->category->nome }}</span>
                                <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">{{ $s->category->categoria }}</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $s->data->format('d/m/Y') }}{{ $s->note ? ' · ' . $s->note : '' }}</div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <span class="font-semibold text-sm text-red-500 mr-1">{{ number_format($s->importo, 2, ',', '.') }} €</span>
                            <a href="{{ route('budget.edit', $s) }}" class="text-slate-400 hover:text-indigo-500 transition p-2 -m-1 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('budget.destroy', $s) }}">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Eliminare questa spesa?')" class="text-slate-400 hover:text-red-500 transition p-2 -m-1 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="px-4 py-3 bg-slate-50 flex justify-between items-center">
                <span class="text-sm font-semibold text-slate-600">Totale</span>
                <span class="font-bold text-red-500">{{ number_format($totale, 2, ',', '.') }} €</span>
            </div>
        </div>

        @else
            <div class="px-5 py-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm">Nessuna spesa trovata con questi filtri</p>
                <a href="{{ route('budget.index') }}" class="mt-3 inline-block text-indigo-600 text-sm font-medium hover:underline">Inserisci la prima spesa →</a>
            </div>
        @endif
    </div>

</div>
</x-app-layout>
