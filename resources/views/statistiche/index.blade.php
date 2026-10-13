<x-app-layout>
@php
    $nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
@endphp
<div class="px-4 py-6 max-w-7xl mx-auto space-y-5">

    <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Statistiche {{ $anno }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Spesa mensile per categoria</p>
        </div>
        <form method="GET" action="{{ route('statistiche.index') }}">
            <label class="block text-xs font-medium text-slate-500 mb-1">Anno</label>
            <select name="anno" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 pr-8 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach(array_reverse($anni) as $a)
                    <option value="{{ $a }}" {{ $a === $anno ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Riepilogo --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Totale anno</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($totale, 0, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Media mensile</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($media, 0, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Budget mensile</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($budgetMensile, 0, ',', '.') }} €</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Mese più caro</div>
            <div class="text-lg font-bold text-slate-800">{{ $meseMax ? $nomiMesi[$meseMax] : '—' }}</div>
        </div>
    </div>

    {{-- Istogramma impilato --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        @if($totale > 0)
            <div class="relative h-[420px] sm:h-[520px]">
                <canvas id="graficoAnno"></canvas>
            </div>
        @else
            <p class="py-12 text-center text-sm text-slate-400">Nessuna spesa registrata nel {{ $anno }}</p>
        @endif
    </div>

    {{-- Tabella mese × categoria --}}
    @if($totale > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Mese</th>
                        @foreach($serie as $cat => $_)
                            <th class="px-4 py-3 text-right">
                                <span class="inline-block w-2.5 h-2.5 rounded-sm mr-1 align-middle" style="background: {{ $colori[$cat] }}"></span>{{ $cat }}
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Totale</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($mesi as $m => $totMese)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-2.5">
                                <a href="{{ route('budget.spese', ['anno' => $anno, 'mese' => $m + 1]) }}" class="text-slate-700 hover:text-primary-600">{{ $nomiMesi[$m + 1] }}</a>
                            </td>
                            @foreach($serie as $cat => $valori)
                                <td class="px-4 py-2.5 text-right {{ $valori[$m] > 0 ? 'text-slate-600' : 'text-slate-300' }}">
                                    {{ $valori[$m] > 0 ? number_format($valori[$m], 0, ',', '.') . ' €' : '—' }}
                                </td>
                            @endforeach
                            <td class="px-4 py-2.5 text-right font-semibold {{ $budgetMensile > 0 && $totMese > $budgetMensile ? 'text-red-500' : 'text-slate-800' }}">
                                {{ number_format($totMese, 0, ',', '.') }} €
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t border-slate-100 font-bold">
                        <td class="px-4 py-3 text-slate-600">Totale</td>
                        @foreach($serie as $valori)
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format(array_sum($valori), 0, ',', '.') }} €</td>
                        @endforeach
                        <td class="px-4 py-3 text-right text-slate-800">{{ number_format($totale, 0, ',', '.') }} €</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const serie  = @json($serie);
        const colori = @json($colori);
        const totali = @json($mesi);
        const budget = {{ $budgetMensile }};
        const euro   = v => v.toLocaleString('it-IT', { maximumFractionDigits: 0 }) + ' €';
        const speseUrl = @json(route('budget.spese'));
        const altre    = @json(\App\Http\Controllers\StatisticheController::ALTRE);

        // Segmento di barra esattamente sotto il puntatore (esclusa la linea del budget)
        const segmentoSotto = (evt, chart) => chart
            .getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true)
            .find(el => chart.data.datasets[el.datasetIndex].type !== 'line');

        const datasets = Object.keys(serie).map(cat => ({
            label: cat,
            data: serie[cat],
            backgroundColor: colori[cat],
            borderColor: '#ffffff',   // separatore tra i segmenti
            borderWidth: { top: 2 },
            borderSkipped: 'bottom',
            borderRadius: 0,
            maxBarThickness: 48,
            stack: 'spese',
        }));
        // Arrotonda solo il segmento visibile più alto di ogni barra
        datasets.forEach((ds, idx) => {
            ds.borderRadius = ctx => {
                const later = datasets.slice(idx + 1).some((d, j) =>
                    d.type !== 'line' && d.data[ctx.dataIndex] > 0 && ctx.chart.isDatasetVisible(idx + 1 + j));
                return later ? 0 : { topLeft: 4, topRight: 4 };
            };
        });

        if (budget > 0) {
            datasets.push({
                type: 'line',
                label: 'Budget mensile',
                data: totali.map(() => budget),
                borderColor: '#52514e',
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                pointHoverRadius: 0,
                fill: false,
            });
        }

        new Chart(document.getElementById('graficoAnno').getContext('2d'), {
            type: 'bar',
            data: { labels: ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'], datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                // Click su un segmento → Lista Spese filtrata per anno, mese e categoria
                onClick: (evt, _, chart) => {
                    const el = segmentoSotto(evt, chart);
                    if (!el) return;
                    const params = new URLSearchParams({ anno: {{ $anno }}, mese: el.index + 1 });
                    const cat = chart.data.datasets[el.datasetIndex].label;
                    if (cat !== altre) params.set('categoria', cat);
                    window.location.href = speseUrl + '?' + params;
                },
                onHover: (evt, _, chart) => {
                    chart.canvas.style.cursor = segmentoSotto(evt, chart) ? 'pointer' : 'default';
                },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, color: '#52514e' } },
                    tooltip: {
                        itemSort: (a, b) => b.datasetIndex - a.datasetIndex,
                        filter: i => i.dataset.type === 'line' || i.parsed.y > 0,
                        callbacks: {
                            label: c => `${c.dataset.label}: ${euro(c.parsed.y)}`,
                            // Somma solo le categorie visibili (quelle non tolte dalla legenda)
                            footer: items => {
                                if (!items.length) return '';
                                const chart = items[0].chart, m = items[0].dataIndex;
                                const tot = chart.data.datasets.reduce((s, d, i) =>
                                    d.type !== 'line' && chart.isDatasetVisible(i) ? s + d.data[m] : s, 0);
                                return 'Totale: ' + euro(tot);
                            },
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 }, color: '#52514e' } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false },
                         ticks: { font: { size: 11 }, color: '#52514e', callback: v => euro(v) } }
                }
            }
        });
    })();
    </script>
    @endif

</div>
</x-app-layout>
