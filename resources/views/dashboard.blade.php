<x-app-layout>
<div class="px-4 py-6 max-w-2xl mx-auto space-y-4">

    <div class="flex items-baseline justify-between">
        <h1 class="text-xl font-bold text-slate-900">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</h1>
        @php
            $totBudget = $righe->sum('budgetMensile');
            $totSpeso  = $righe->sum('speso');
            $percTot   = $totBudget > 0 ? min(100, round($totSpeso / $totBudget * 100)) : 0;
        @endphp
        <span class="text-sm text-slate-500">
            {{ number_format($totSpeso, 0, ',', '.') }} / {{ number_format($totBudget, 0, ',', '.') }} €
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($righe as $cat => $r)
            @php
                $barM  = $r['perc']     > 90 ? 'bg-red-500'  : ($r['perc']     > 70 ? 'bg-amber-400' : 'bg-emerald-500');
                $barA  = $r['percAnno'] > 90 ? 'bg-red-400'  : ($r['percAnno'] > 70 ? 'bg-amber-300' : 'bg-emerald-400');
                $textM = $r['perc']     > 90 ? 'text-red-500': ($r['perc']     > 70 ? 'text-amber-500': 'text-emerald-600');
                $url   = route('budget.spese', ['anno' => $anno, 'mese' => $mese, 'categoria' => $cat]);
            @endphp
            <a href="{{ $url }}"
                class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4 hover:shadow-md hover:border-slate-200 transition-all block">

                <div class="flex items-start justify-between mb-3">
                    <span class="text-sm font-semibold text-slate-800 leading-tight">{{ $cat }}</span>
                    <span class="text-xs font-bold {{ $textM }} ml-2 flex-shrink-0">{{ $r['perc'] }}%</span>
                </div>

                {{-- Barra mensile --}}
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                    <div class="{{ $barM }} h-2.5 rounded-full transition-all" style="width: {{ $r['perc'] }}%"></div>
                </div>
                <div class="flex items-baseline justify-between mb-3">
                    <span class="text-lg font-bold text-slate-800">{{ number_format($r['speso'], 0, ',', '.') }} €</span>
                    <span class="text-xs text-slate-400">/ {{ number_format($r['budgetMensile'], 0, ',', '.') }} € mese</span>
                </div>

                {{-- Barra annuale --}}
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                    <div class="{{ $barA }} h-1.5 rounded-full transition-all" style="width: {{ $r['percAnno'] }}%"></div>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-xs text-slate-500 font-medium">{{ number_format($r['spesoAnno'], 0, ',', '.') }} €</span>
                    <span class="text-xs text-slate-400">/ {{ number_format($r['budgetAnnuale'], 0, ',', '.') }} € anno</span>
                </div>

            </a>
        @endforeach
    </div>

</div>
</x-app-layout>
