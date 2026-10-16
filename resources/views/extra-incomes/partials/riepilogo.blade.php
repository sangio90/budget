{{--
    Riepilogo annuale entrate extra: per categoria, per immobile e tabella mese × categoria.
    Parametri: $riepilogo (ExtraIncome::riepilogoAnno), $anno
--}}
@php
    $nomiMesiR = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $maxCat    = max(array_map('abs', $riepilogo['perCategoria'] ?: [0])) ?: 1;
    $maxImm    = max(array_map('abs', $riepilogo['perImmobile'] ?: [0])) ?: 1;
@endphp

@if($riepilogo['conteggio'] > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- Per categoria --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Per categoria {{ $anno }}</h2>
        </div>
        <div class="px-5 py-4 space-y-3">
            @foreach($riepilogo['perCategoria'] as $cat => $tot)
                <a href="{{ route('extra-incomes.index', ['anno' => $anno, 'categoria' => $cat]) }}" class="block group">
                    <div class="flex items-baseline justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700 group-hover:text-primary-600">{{ $cat }}</span>
                        <span class="font-semibold whitespace-nowrap {{ $tot >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($tot, 2, ',', '.') }} €</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-emerald-400 h-1.5 rounded-full" style="width: {{ max(0, round($tot / $maxCat * 100)) }}%"></div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Per immobile --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-800">Per immobile {{ $anno }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">Affitti e rimborsi spese</p>
        </div>
        <div class="px-5 py-4 space-y-3">
            @forelse($riepilogo['perImmobile'] as $imm => $tot)
                <a href="{{ route('extra-incomes.index', ['anno' => $anno, 'immobile' => $imm]) }}" class="block group">
                    <div class="flex items-baseline justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700 group-hover:text-primary-600">{{ $imm }}</span>
                        <span class="font-semibold whitespace-nowrap {{ $tot >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($tot, 2, ',', '.') }} €</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ max(0, round($tot / $maxImm * 100)) }}%"></div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-400 text-center py-4">Nessuna entrata legata a un immobile</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Tabella mese × categoria --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">Riepilogo Mensile {{ $anno }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Mese</th>
                    @foreach($riepilogo['mesiCategoria'] as $cat => $_)
                        <th class="px-4 py-3 text-right">{{ $cat }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Totale</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($riepilogo['perMese'] as $m => $totMese)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-2.5">
                            <a href="{{ route('extra-incomes.index', ['anno' => $anno, 'mese' => $m]) }}" class="text-slate-700 hover:text-primary-600">{{ $nomiMesiR[$m] }}</a>
                        </td>
                        @foreach($riepilogo['mesiCategoria'] as $valori)
                            <td class="px-4 py-2.5 text-right {{ $valori[$m] != 0 ? 'text-slate-600' : 'text-slate-300' }}">
                                {{ $valori[$m] != 0 ? number_format($valori[$m], 2, ',', '.') . ' €' : '—' }}
                            </td>
                        @endforeach
                        <td class="px-4 py-2.5 text-right font-semibold {{ $totMese > 0 ? 'text-emerald-600' : ($totMese < 0 ? 'text-red-500' : 'text-slate-300') }}">
                            {{ $totMese != 0 ? number_format($totMese, 2, ',', '.') . ' €' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-slate-50 border-t border-slate-100 font-bold">
                    <td class="px-4 py-3 text-slate-600">Totale</td>
                    @foreach($riepilogo['perCategoria'] as $tot)
                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($tot, 2, ',', '.') }} €</td>
                    @endforeach
                    <td class="px-4 py-3 text-right text-emerald-700">{{ number_format($riepilogo['totale'], 2, ',', '.') }} €</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-12 text-center text-slate-400">
    <p class="text-sm">Nessuna entrata extra registrata nel {{ $anno }}</p>
</div>
@endif
