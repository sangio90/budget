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

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="divide-y divide-slate-50">
            @foreach($righe as $cat => $r)
                @php
                    $bar = $r['perc'] > 90 ? 'bg-red-500' : ($r['perc'] > 70 ? 'bg-amber-400' : 'bg-indigo-500');
                @endphp
                <div class="px-5 py-3.5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-800">{{ $cat }}</span>
                        <div class="text-right">
                            <span class="text-sm font-semibold {{ $r['perc'] > 90 ? 'text-red-500' : 'text-slate-700' }}">
                                {{ number_format($r['speso'], 0, ',', '.') }} €
                            </span>
                            <span class="text-xs text-slate-400"> / {{ number_format($r['budgetMensile'], 0, ',', '.') }} €</span>
                        </div>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="{{ $bar }} h-2 rounded-full" style="width: {{ $r['perc'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
</x-app-layout>
