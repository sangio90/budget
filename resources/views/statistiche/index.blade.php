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

    {{-- Riepilogo: valori su tutte le categorie + valori filtrati quando se ne spegne qualcuna dalla legenda --}}
    <div id="filtroAttivo" class="hidden flex-wrap items-center gap-2 text-sm bg-primary-50 border border-primary-100 text-primary-700 rounded-xl px-4 py-2.5">
        <span>Categorie nascoste: <span data-nascoste class="font-semibold"></span></span>
        <button type="button" data-ripristina class="ml-auto text-xs font-semibold underline hover:no-underline">Mostra tutte</button>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Totale anno</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($totale, 0, ',', '.') }} €</div>
            <div data-filtrato="totale" class="hidden mt-1.5 pt-1.5 border-t border-dashed border-slate-200">
                <div class="text-xs text-slate-400">Filtrato</div>
                <div data-valore class="text-base font-bold text-primary-600"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Media mensile</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($media, 0, ',', '.') }} €</div>
            <div data-filtrato="media" class="hidden mt-1.5 pt-1.5 border-t border-dashed border-slate-200">
                <div class="text-xs text-slate-400">Filtrato</div>
                <div data-valore class="text-base font-bold text-primary-600"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Budget mensile</div>
            <div class="text-lg font-bold text-slate-800">{{ number_format($budgetMensile, 0, ',', '.') }} €</div>
            <div data-filtrato="budget" class="hidden mt-1.5 pt-1.5 border-t border-dashed border-slate-200">
                <div class="text-xs text-slate-400">Filtrato</div>
                <div data-valore class="text-base font-bold text-primary-600"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4">
            <div class="text-xs text-slate-400 mb-0.5">Mese più caro</div>
            <div class="text-lg font-bold text-slate-800">{{ $meseMax ? $nomiMesi[$meseMax] : '—' }}</div>
            <div data-filtrato="meseMax" class="hidden mt-1.5 pt-1.5 border-t border-dashed border-slate-200">
                <div class="text-xs text-slate-400">Filtrato</div>
                <div data-valore class="text-base font-bold text-primary-600"></div>
            </div>
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
        const serie    = @json($serie);
        const colori   = @json($colori);
        const budget   = {{ $budgetMensile }};
        const budgetPerSerie = @json($budgetPerSerie);
        const mesiTrascorsi  = {{ $mesiTrascorsi }};
        const nomiMesi = @json(array_slice($nomiMesi, 1));
        const speseUrl = @json(route('budget.spese'));
        const altre    = @json(\App\Http\Controllers\StatisticheController::ALTRE);
        const altreCategorie = @json($altreCategorie);
        const euro     = v => v.toLocaleString('it-IT', { maximumFractionDigits: 0 }) + ' €';
        const categorie = Object.keys(serie);

        // Ogni barra è ordinata per il suo mese: la spesa più grande in basso, la più piccola in alto.
        // Chart.js impila sempre nello stesso ordine, quindi uso barre "flottanti" [inizio, fine]
        // calcolate qui e ricalcolate quando una categoria viene tolta dalla legenda.
        function impila(chart) {
            const visibili = categorie.map((_, i) => !chart || chart.isDatasetVisible(i));
            const range = categorie.map(() => Array(12).fill(null));
            for (let m = 0; m < 12; m++) {
                let base = 0;
                categorie
                    .map((cat, i) => ({ i, v: serie[cat][m] }))
                    .filter(s => visibili[s.i] && s.v > 0)
                    .sort((a, b) => b.v - a.v)
                    .forEach(s => { range[s.i][m] = [base, base + s.v]; base += s.v; });
            }
            return range;
        }

        const totaleVisibile = (chart, m) =>
            categorie.reduce((s, cat, i) => chart.isDatasetVisible(i) ? s + serie[cat][m] : s, 0);

        // Segmento di barra esattamente sotto il puntatore (esclusa la linea del budget)
        const segmentoSotto = (evt, chart) => chart
            .getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true)
            .find(el => chart.data.datasets[el.datasetIndex].type !== 'line');

        const iniziale = impila(null);
        const datasets = categorie.map((cat, i) => ({
            label: cat,
            data: iniziale[i],
            backgroundColor: colori[cat],
            borderColor: '#ffffff',   // separatore tra i segmenti
            borderWidth: { top: 2 },
            borderSkipped: 'bottom',
            maxBarThickness: 48,
            grouped: false,
            // Arrotonda solo il segmento più alto di ogni barra
            borderRadius: ctx => ctx.raw && Math.abs(ctx.raw[1] - totaleVisibile(ctx.chart, ctx.dataIndex)) < 0.005
                ? { topLeft: 4, topRight: 4 } : 0,
        }));

        if (budget > 0) {
            datasets.push({
                type: 'line',
                label: 'Budget mensile',
                data: Array(12).fill(budget),
                borderColor: '#52514e',
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
                pointHoverRadius: 0,
                fill: false,
            });
        }

        // Dopo aver acceso/spento categorie: ricalcola le barre, la linea del budget e i riquadri "Filtrato"
        function applicaFiltro(chart) {
            const range = impila(chart);
            categorie.forEach((_, i) => chart.data.datasets[i].data = range[i]);

            const nascoste = categorie.filter((_, i) => !chart.isDatasetVisible(i));
            const filtrato = nascoste.length > 0;
            const budgetF  = budget - nascoste.reduce((s, c) => s + (budgetPerSerie[c] || 0), 0);

            const linea = chart.data.datasets.find(d => d.type === 'line');
            if (linea) {
                linea.data  = Array(12).fill(budgetF);
                linea.label = filtrato ? 'Budget mensile (filtrato)' : 'Budget mensile';
            }
            chart.update();

            const banner = document.getElementById('filtroAttivo');
            banner.classList.toggle('hidden', !filtrato);
            banner.classList.toggle('flex', filtrato);
            banner.querySelector('[data-nascoste]').textContent = nascoste.join(', ');

            const mesi    = Array.from({ length: 12 }, (_, m) => totaleVisibile(chart, m));
            const totale  = mesi.reduce((a, b) => a + b, 0);
            const meseMax = totale > 0 ? mesi.indexOf(Math.max(...mesi)) : -1;
            const valori  = {
                totale:  euro(totale),
                media:   euro(totale / mesiTrascorsi),
                budget:  euro(budgetF),
                meseMax: meseMax >= 0 ? nomiMesi[meseMax] : '—',
            };
            document.querySelectorAll('[data-filtrato]').forEach(el => {
                el.classList.toggle('hidden', !filtrato);
                el.querySelector('[data-valore]').textContent = valori[el.dataset.filtrato];
            });
        }

        const grafico = new Chart(document.getElementById('graficoAnno').getContext('2d'), {
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
                    // "Altre categorie" → tutte le categorie tranne quelle mostrate nel grafico
                    (cat === altre ? altreCategorie : [cat]).forEach(c => params.append('categoria[]', c));
                    window.location.href = speseUrl + '?' + params;
                },
                onHover: (evt, _, chart) => {
                    chart.canvas.style.cursor = segmentoSotto(evt, chart) ? 'pointer' : 'default';
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11 }, color: '#52514e' },
                        onClick(evt, item, legend) {
                            Chart.defaults.plugins.legend.onClick.call(this, evt, item, legend);
                            applicaFiltro(legend.chart);
                        },
                    },
                    tooltip: {
                        // Stesso ordine della barra: in alto la spesa più piccola
                        itemSort: (a, b) => (a.raw?.[1] ?? Infinity) > (b.raw?.[1] ?? Infinity) ? -1 : 1,
                        filter: i => i.dataset.type === 'line' || i.raw !== null,
                        callbacks: {
                            label: c => `${c.dataset.label}: ${euro(c.dataset.type === 'line' ? c.parsed.y : c.raw[1] - c.raw[0])}`,
                            // Somma solo le categorie visibili (quelle non tolte dalla legenda)
                            footer: items => items.length ? 'Totale: ' + euro(totaleVisibile(items[0].chart, items[0].dataIndex)) : '',
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#52514e' } },
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false },
                         ticks: { font: { size: 11 }, color: '#52514e', callback: v => euro(v) } }
                }
            }
        });

        document.querySelector('#filtroAttivo [data-ripristina]').addEventListener('click', () => {
            categorie.forEach((_, i) => grafico.setDatasetVisibility(i, true));
            applicaFiltro(grafico);
        });
    })();
    </script>
    @endif

</div>
</x-app-layout>
