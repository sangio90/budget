<x-app-layout>
@php
    $nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $meseTop  = $riepilogo['totale'] > 0 ? array_search(max($riepilogo['perMese']), $riepilogo['perMese']) : null;
    $filtri   = array_filter(['mese' => $mese, 'categoria' => $categoria, 'immobile' => $immobile], 'strlen');
    $iconaModifica = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
    $iconaElimina  = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp
<div class="px-4 py-6 max-w-7xl mx-auto space-y-6">

    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Entrate Extra</h1>
            <p class="text-sm text-slate-500 mt-0.5">Affitti, fotovoltaico, cedole, interessi e rimborsi spese immobili</p>
        </div>
        <form method="GET" action="{{ route('extra-incomes.index') }}">
            @foreach($filtri as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
            <select name="anno" onchange="this.form.submit()" class="text-sm font-semibold border border-slate-200 rounded-xl pl-4 pr-9 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach($anni as $a)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Totale {{ $anno }}</div>
            <div class="text-2xl font-bold whitespace-nowrap text-emerald-600">{{ number_format($riepilogo['totale'], 2, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Media mensile</div>
            <div class="text-2xl font-bold whitespace-nowrap text-slate-800">{{ number_format($riepilogo['media'], 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-400 mt-1">su {{ $riepilogo['mesiTrascorsi'] }} mesi</div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Mese migliore</div>
            <div class="text-2xl font-bold whitespace-nowrap text-slate-800">{{ $meseTop ? $nomiMesi[$meseTop] : '—' }}</div>
            @if($meseTop)<div class="text-xs text-slate-400 mt-1">{{ number_format($riepilogo['perMese'][$meseTop], 2, ',', '.') }} €</div>@endif
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Movimenti {{ $anno }}</div>
            <div class="text-2xl font-bold whitespace-nowrap text-slate-800">{{ $riepilogo['conteggio'] }}</div>
        </div>
    </div>

    @include('extra-incomes.partials.riepilogo', ['riepilogo' => $riepilogo, 'anno' => $anno])

    {{-- Form inserimento --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Inserisci Entrata Extra</h2>
        </div>
        <form method="POST" action="{{ route('extra-incomes.store') }}" class="px-5 py-4 space-y-4">
            @csrf
            @include('extra-incomes.partials.campi', ['entrata' => null])
            <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Registra Entrata
            </button>
        </form>
    </div>

    {{-- Filtri lista --}}
    <div class="flex gap-2 flex-wrap">
        <form method="GET" action="{{ route('extra-incomes.index') }}" class="flex gap-2 flex-wrap w-full sm:w-auto">
            <input type="hidden" name="anno" value="{{ $anno }}">
            <select name="mese" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg pl-3 pr-8 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-[10rem]">
                <option value="">Tutti i mesi</option>
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $mese == $m ? 'selected' : '' }}>{{ $nomiMesi[$m] }}</option>
                @endforeach
            </select>
            <select name="categoria" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg pl-3 pr-8 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-[10rem]">
                <option value="">Tutte le categorie</option>
                @foreach($categorie as $c)
                    <option value="{{ $c }}" {{ $categoria === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
            <select name="immobile" onchange="this.form.submit()" class="text-sm border border-slate-200 rounded-lg pl-3 pr-8 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-[10rem]">
                <option value="">Tutti gli immobili</option>
                @foreach($immobili as $i)
                    <option value="{{ $i }}" {{ $immobile === $i ? 'selected' : '' }}>{{ $i }}</option>
                @endforeach
            </select>
            @if($filtri)
                <a href="{{ route('extra-incomes.index', ['anno' => $anno]) }}" class="text-sm text-slate-500 hover:text-primary-600 px-2 py-2">Azzera filtri</a>
            @endif
        </form>
    </div>

    {{-- Lista entrate --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-baseline justify-between gap-3">
            <h2 class="font-semibold text-slate-800">Entrate ({{ $entrate->count() }})</h2>
            <span class="text-sm font-semibold whitespace-nowrap {{ $totaleFiltrato >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($totaleFiltrato, 2, ',', '.') }} €</span>
        </div>

        @if($entrate->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm hidden sm:table">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3 text-left">Categoria</th>
                        <th class="px-5 py-3 text-left">Immobile</th>
                        <th class="px-5 py-3 text-left">Causale</th>
                        <th class="px-5 py-3 text-right">Importo</th>
                        <th class="px-5 py-3 text-left">Note</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($entrate as $e)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3 text-slate-500">{{ $e->data->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700 whitespace-nowrap">{{ $e->categoria }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $e->immobile ?? '—' }}</td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $e->causale }}</td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap {{ $e->importo >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ $e->importo >= 0 ? '+' : '' }}{{ number_format($e->importo, 2, ',', '.') }} €</td>
                            <td class="px-5 py-3 text-slate-400 text-xs max-w-xs truncate">{{ $e->note }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('extra-incomes.edit', $e) }}" class="inline-block text-slate-300 hover:text-primary-500 transition align-middle mr-2" title="Modifica">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconaModifica }}"/></svg>
                                </a>
                                <form method="POST" action="{{ route('extra-incomes.destroy', $e) }}" class="inline-block align-middle" onsubmit="return confirm('Eliminare questa entrata?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-400 transition" title="Elimina">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconaElimina }}"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Mobile card list --}}
            <div class="sm:hidden divide-y divide-slate-50">
                @foreach($entrate as $e)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <a href="{{ route('extra-incomes.edit', $e) }}" class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-700 text-sm truncate">{{ $e->causale }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium bg-emerald-100 text-emerald-700 flex-shrink-0">{{ $e->categoria }}</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5 truncate">{{ $e->data->format('d/m/Y') }}{{ $e->immobile ? ' · ' . $e->immobile : '' }}{{ $e->note ? ' · ' . $e->note : '' }}</div>
                        </a>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="font-semibold text-sm whitespace-nowrap {{ $e->importo >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ $e->importo >= 0 ? '+' : '' }}{{ number_format($e->importo, 2, ',', '.') }} €</span>
                            <form method="POST" action="{{ route('extra-incomes.destroy', $e) }}" onsubmit="return confirm('Eliminare?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-300 hover:text-red-400 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconaElimina }}"/></svg>
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
                <p class="text-sm">Nessuna entrata extra trovata</p>
            </div>
        @endif
    </div>

</div>
</x-app-layout>
