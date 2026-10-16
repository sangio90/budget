{{-- Confronto su tre anni (foglio "Proiezione 2026-2028") --}}
@php
    $anniT  = array_keys($triennio);
    $primoT = $triennio[$anniT[0]];
    $righeT = [
        ['Compensi (imponibile)', 'compensi', ''],
        ['Costi attività', 'costi', ''],
        ['Reddito professionale', 'reddito', 'font-semibold'],
        ['INPS di competenza', 'inps', ''],
        ['INPS versato nell\'anno (deducibile)', 'inps_deducibile', 'text-slate-400'],
        ['Reddito imponibile IRPEF', 'imponibile_irpef', ''],
        ['IRPEF lorda', 'irpef_lorda', ''],
        ['− Detrazioni', 'detrazioni', 'text-slate-400'],
        ['IRPEF netta', 'irpef_netta', 'font-semibold'],
        ['Ritenute subite', 'ritenute', 'text-slate-400'],
        ['IRPEF a debito (+) / credito (−)', 'irpef_saldo', ''],
        ['Addizionale regionale', 'add_regionale', ''],
        ['Addizionale comunale', 'add_comunale', ''],
        ['Cedolare secca', 'cedolare', ''],
        ['Totale imposte e contributi', 'totale_imposte', 'font-semibold'],
        ['Affitti incassati', 'affitti', 'text-slate-400'],
        ['Netto annuo', 'netto', 'font-bold'],
    ];
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="font-semibold text-slate-800">Proiezione {{ $anniT[0] }}–{{ end($anniT) }}</h2>
        <p class="text-xs text-slate-400 mt-0.5">A reddito invariato: il netto scende quando finiscono le detrazioni</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3 text-left"></th>
                    @foreach($anniT as $a)<th class="px-5 py-3 text-right">{{ $a }}</th>@endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($righeT as [$etichetta, $chiave, $classe])
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-2 text-slate-600 {{ $classe }}">{{ $etichetta }}</td>
                        @foreach($triennio as $r)
                            <td class="px-5 py-2 text-right {{ $classe }}">{{ $eu($r[$chiave]) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-primary-50 font-bold">
                    <td class="px-5 py-3 text-primary-700">Netto mensile</td>
                    @foreach($triennio as $r)<td class="px-5 py-3 text-right text-primary-700">{{ $eu($r['netto_mensile']) }}</td>@endforeach
                </tr>
                <tr class="bg-slate-50">
                    <td class="px-5 py-2 text-slate-500">Differenza mensile vs {{ $anniT[0] }}</td>
                    @foreach($triennio as $r)
                        @php $d = $r['netto_mensile'] - $primoT['netto_mensile']; @endphp
                        <td class="px-5 py-2 text-right font-semibold {{ $d < 0 ? 'text-red-500' : 'text-slate-500' }}">{{ $d == 0 ? '—' : $eu($d) }}</td>
                    @endforeach
                </tr>
                <tr class="bg-slate-50">
                    <td class="px-5 py-2 text-slate-500">Accantonamento mensile consigliato</td>
                    @foreach($triennio as $r)<td class="px-5 py-2 text-right text-slate-700">{{ $eu($r['accantonamento_mensile']) }}</td>@endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
