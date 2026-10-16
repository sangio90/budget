<x-app-layout>
@php
    $eu   = fn($v, $d = 0) => $v === null ? '—' : number_format((float) $v, $d, ',', '.') . ' €';
    $pc   = fn($v, $d = 1) => $v === null ? '—' : number_format((float) $v * 100, $d, ',', '.') . '%';
    $inCorso = $anno === $oggi->year;
    $futuro  = $anno > $oggi->year;
    $mesi    = $ipotesi['mesi'];
    $fonte   = $ipotesi['fonte'];
    $card    = 'bg-white rounded-2xl p-5 shadow-sm border border-slate-100';
    $kpiLbl  = 'text-xs font-medium text-slate-500 uppercase tracking-wide mb-1';
    $badgeStato = [
        'stimato'    => 'bg-slate-100 text-slate-600',
        'da_pagare'  => 'bg-amber-100 text-amber-700',
        'pagato'     => 'bg-emerald-100 text-emerald-700',
        'compensato' => 'bg-primary-100 text-primary-700',
    ];
@endphp
<div class="px-4 py-6 max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Fiscale {{ $anno }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">IVA trimestrale, INPS gestione separata, IRPEF e scadenze</p>
        </div>
        <form method="GET" action="{{ route('fiscale.index') }}">
            <select name="anno" onchange="this.form.submit()" class="text-sm font-semibold border border-slate-200 rounded-xl pl-4 pr-9 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                @foreach($anni as $a)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($errors->any())
        <div class="p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
            @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
    @endif

    {{-- Consuntivo a oggi --}}
    @if($consuntivo)
    <div>
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
            {{ $inCorso ? 'A oggi (' . $oggi->format('d/m/Y') . ' · ' . number_format($mesi, 1, ',', '.') . ' mesi)' : 'Consuntivo ' . $anno }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Incassato</div><div class="text-xl font-bold whitespace-nowrap text-emerald-600">{{ $eu($consuntivo['incassato']) }}</div><div class="text-xs text-slate-400 mt-1">lordo dai clienti</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Imponibile</div><div class="text-xl font-bold whitespace-nowrap text-slate-800">{{ $eu($consuntivo['imponibile']) }}</div><div class="text-xs text-slate-400 mt-1">incasso / fattore</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">IVA incassata</div><div class="text-xl font-bold whitespace-nowrap text-slate-800">{{ $eu($consuntivo['iva_incassata']) }}</div><div class="text-xs text-slate-400 mt-1">versata {{ $eu($ivaVersata) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Ritenute subite</div><div class="text-xl font-bold whitespace-nowrap text-slate-800">{{ $eu($consuntivo['ritenute']) }}</div><div class="text-xs text-slate-400 mt-1">20% già trattenuto</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Costi deducibili</div><div class="text-xl font-bold whitespace-nowrap text-red-500">{{ $eu($consuntivo['costi']) }}</div><div class="text-xs text-slate-400 mt-1">collab. {{ $eu($consuntivo['collaboratori']) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">F24 versati</div><div class="text-xl font-bold whitespace-nowrap text-amber-500">{{ $eu($consuntivo['f24']) }}</div><div class="text-xs text-slate-400 mt-1">da Entrate/Uscite</div></div>
        </div>
    </div>
    @endif

    {{-- Imposte e netto dell'anno --}}
    <div>
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
            Imposte e netto {{ $anno }}
            @if($inCorso)<span class="ml-1 normal-case font-normal text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">stima: a oggi + ritmo degli ultimi 3 mesi × {{ number_format(12 - $mesi, 1, ',', '.') }} mesi</span>@endif
            @if($futuro)<span class="ml-1 normal-case font-normal text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">proiezione</span>@endif
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Reddito professionale</div><div class="text-xl font-bold whitespace-nowrap text-slate-800">{{ $eu($risultato['reddito']) }}</div><div class="text-xs text-slate-400 mt-1">compensi {{ $eu($risultato['compensi']) }} − costi {{ $eu($risultato['costi']) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">INPS</div><div class="text-xl font-bold whitespace-nowrap text-red-500">{{ $eu($risultato['inps']) }}</div><div class="text-xs text-slate-400 mt-1">{{ $pc($parametri->aliquota_inps, 2) }} fino a {{ $eu($parametri->massimale_inps) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">IRPEF netta</div><div class="text-xl font-bold whitespace-nowrap text-red-500">{{ $eu($risultato['irpef_netta']) }}</div><div class="text-xs text-slate-400 mt-1">lorda {{ $eu($risultato['irpef_lorda']) }} − detraz. {{ $eu($risultato['detrazioni']) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">IRPEF dopo ritenute</div><div class="text-xl font-bold whitespace-nowrap {{ $risultato['irpef_saldo'] > 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $eu($risultato['irpef_saldo']) }}</div><div class="text-xs text-slate-400 mt-1">{{ $risultato['irpef_saldo'] > 0 ? 'a debito' : 'a credito' }} · ritenute {{ $eu($risultato['ritenute']) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Addizionali</div><div class="text-xl font-bold whitespace-nowrap text-red-500">{{ $eu($risultato['add_regionale'] + $risultato['add_comunale']) }}</div><div class="text-xs text-slate-400 mt-1">reg. {{ $eu($risultato['add_regionale']) }} · com. {{ $eu($risultato['add_comunale']) }}</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Cedolare secca</div><div class="text-xl font-bold whitespace-nowrap text-red-500">{{ $eu($risultato['cedolare']) }}</div><div class="text-xs text-slate-400 mt-1">affitti</div></div>
            <div class="{{ $card }}"><div class="{{ $kpiLbl }}">Totale imposte</div><div class="text-xl font-bold whitespace-nowrap text-red-600">{{ $eu($risultato['totale_imposte']) }}</div><div class="text-xs text-slate-400 mt-1">INPS + IRPEF + addiz. + cedolare</div></div>
            <div class="bg-primary-50 rounded-2xl p-5 shadow-sm border border-primary-100">
                <div class="text-xs font-medium text-primary-600 uppercase tracking-wide mb-1">Netto</div>
                <div class="text-xl font-bold whitespace-nowrap text-primary-700">{{ $eu($risultato['netto_mensile']) }} <span class="text-sm font-medium">/ mese</span></div>
                <div class="text-xs text-primary-400 mt-1">{{ $eu($risultato['netto']) }} anno · affitti inclusi</div>
            </div>
        </div>

        {{-- Dettaglio IRPEF per scaglione --}}
        <details class="mt-3 bg-white rounded-2xl shadow-sm border border-slate-100" open>
            <summary class="cursor-pointer select-none px-5 py-3 text-sm font-semibold text-slate-700">Calcolo IRPEF per scaglione</summary>
            <div class="px-5 pb-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="text-slate-600">
                        <tr><td class="py-1">Reddito professionale</td><td class="py-1 text-right whitespace-nowrap">{{ $eu($risultato['reddito']) }}</td></tr>
                        <tr><td class="py-1">+ redditi fabbricati</td><td class="py-1 text-right whitespace-nowrap">{{ $eu($risultato['redditi_fabbricati']) }}</td></tr>
                        <tr><td class="py-1">− INPS versato nell'anno (deducibile)</td><td class="py-1 text-right whitespace-nowrap">−{{ $eu($risultato['inps_deducibile']) }}</td></tr>
                        <tr><td class="py-1">− fondo pensione</td><td class="py-1 text-right whitespace-nowrap">−{{ $eu($risultato['fondo_pensione']) }}</td></tr>
                        <tr class="border-t border-slate-100 font-semibold text-slate-800"><td class="py-1">Imponibile IRPEF</td><td class="py-1 text-right whitespace-nowrap">{{ $eu($risultato['imponibile_irpef']) }}</td></tr>
                    </tbody>
                </table>
                <table class="w-full text-sm mt-3">
                    <thead>
                        <tr class="text-xs text-slate-400 uppercase tracking-wide">
                            <th class="text-left font-medium py-1">Scaglione</th>
                            <th class="text-right font-medium py-1">Aliquota</th>
                            <th class="text-right font-medium py-1">Reddito nello scaglione</th>
                            <th class="text-right font-medium py-1">Imposta</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600">
                        @foreach($risultato['irpef_scaglioni'] as $sc)
                        <tr class="border-t border-slate-50 {{ $sc['base'] > 0 ? '' : 'text-slate-300' }}">
                            <td class="py-1 whitespace-nowrap">{{ $sc['fino'] === null ? 'oltre ' . $eu($sc['da']) : $eu($sc['da']) . ' – ' . $eu($sc['fino']) }}</td>
                            <td class="py-1 text-right">{{ $pc($sc['aliquota'], 0) }}</td>
                            <td class="py-1 text-right whitespace-nowrap">{{ $eu($sc['base']) }}</td>
                            <td class="py-1 text-right whitespace-nowrap">{{ $eu($sc['imposta']) }}</td>
                        </tr>
                        @endforeach
                        <tr class="border-t border-slate-200 font-semibold text-slate-800">
                            <td class="py-1" colspan="3">IRPEF lorda</td>
                            <td class="py-1 text-right whitespace-nowrap">{{ $eu($risultato['irpef_lorda']) }}</td>
                        </tr>
                        <tr><td class="py-1" colspan="3">− detrazioni</td><td class="py-1 text-right whitespace-nowrap text-emerald-600">−{{ $eu($risultato['detrazioni']) }}</td></tr>
                        <tr class="font-semibold text-slate-800"><td class="py-1" colspan="3">IRPEF netta</td><td class="py-1 text-right whitespace-nowrap">{{ $eu($risultato['irpef_netta']) }}</td></tr>
                        <tr><td class="py-1" colspan="3">− ritenute subite</td><td class="py-1 text-right whitespace-nowrap text-emerald-600">−{{ $eu($risultato['ritenute']) }}</td></tr>
                        <tr class="border-t border-slate-200 font-semibold {{ $risultato['irpef_saldo'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            <td class="py-1" colspan="3">IRPEF {{ $risultato['irpef_saldo'] > 0 ? 'a debito' : 'a credito' }}</td>
                            <td class="py-1 text-right whitespace-nowrap">{{ $eu(abs($risultato['irpef_saldo'])) }}</td>
                        </tr>
                    </tbody>
                </table>
                @php $marg = collect($risultato['irpef_scaglioni'])->filter(fn ($sc) => $sc['base'] > 0)->last(); @endphp
                @if($marg)
                <p class="text-xs text-slate-400 mt-2">Aliquota marginale {{ $pc($marg['aliquota'], 0) }}: ogni 100 € di costi deducibili in più fanno risparmiare circa {{ $eu(100 * $marg['aliquota']) }} di IRPEF (più le addizionali).</p>
                @endif
            </div>
        </details>
        <p class="text-xs text-slate-400 mt-2">
            Ipotesi: compensi {{ $fonte['compensi'] ?? '' }}, costi {{ $fonte['costi'] ?? '' }}, affitti {{ $fonte['affitti'] ?? '' }}, IVA {{ $fonte['iva_annua'] ?? '' }}.
            Si possono fissare a mano nei parametri dell'anno.
        </p>
    </div>

    {{-- Da avere da parte e accantonamento --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Quanto dovresti avere da parte</h2>
                <p class="text-xs text-slate-400 mt-0.5">Da versare (righe non pagate) dal {{ $oggi->format('d/m/Y') }}, al netto dei crediti</p>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                @foreach([
                    ['Fino alla prossima scadenza grossa' . ($fondo['prossima_grossa'] ? ' (' . $fondo['prossima_grossa']->format('d/m/Y') . ')' : ''), $fondo['fino_prossima_grossa']],
                    ['Fino al ' . $fondo['giugno']->format('d/m/Y'), $fondo['fino_giugno']],
                ] as $i => [$etichetta, $serve])
                    <div class="{{ $i ? 'border-t border-slate-100 pt-3' : '' }}">
                        <div class="flex justify-between items-baseline">
                            <span class="text-slate-600 font-medium">{{ $etichetta }}</span>
                            <span class="text-lg font-bold whitespace-nowrap text-slate-800">{{ $eu($serve, 2) }}</span>
                        </div>
                    </div>
                @endforeach
                @if($fondo['scadute_non_pagate'])
                    <p class="text-xs text-amber-600">{{ $fondo['scadute_non_pagate'] }} scadenze passate non segnate come pagate (escluse dal conto).</p>
                @endif
                @if(! in_array($oggi->year + 1, $fondo['anni_coperti'], true))
                    <p class="text-xs text-amber-600">Mancano le scadenze del {{ $oggi->year + 1 }}: usa "Genera stime {{ $oggi->year + 1 }}" nello scadenziario.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-800">Accantonamento consigliato {{ $anno }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">IVA + INPS + IRPEF a debito + addizionali + cedolare + IMU, di competenza dell'anno</p>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                <div class="flex justify-between items-baseline">
                    <span class="text-slate-500">Al mese</span>
                    <span class="text-lg font-bold whitespace-nowrap text-slate-800">{{ $eu($accantonamento['mensile']) }}</span>
                </div>
                <div class="flex justify-between items-baseline">
                    <span class="text-slate-500">In % dell'incassato lordo</span>
                    <span class="font-semibold whitespace-nowrap text-slate-800">{{ $pc($accantonamento['perc']) }}</span>
                </div>
                <div class="border-t border-slate-100 pt-3 flex justify-between items-baseline">
                    <span class="text-slate-500">Obiettivo {{ $pc($accantonamento['obiettivo'], 0) }} dell'incassato</span>
                    <span class="font-semibold whitespace-nowrap text-primary-700">{{ $eu($accantonamento['obiettivo_mensile']) }} / mese</span>
                </div>
                <div class="text-xs text-slate-400">IVA annua considerata {{ $eu($risultato['iva_annua']) }} · cedolare {{ $eu($risultato['cedolare']) }} · IMU {{ $eu($risultato['imu']) }} · risparmio mensile (netto − spese famiglia) {{ $eu($risultato['risparmio_mensile']) }}</div>
            </div>
        </div>
    </div>

    {{-- Confronto triennio --}}
    @include('fiscale.partials.triennio')

    {{-- Scadenziario --}}
    @include('fiscale.partials.scadenziario')

    {{-- Parametri, clienti, categorie --}}
    @include('fiscale.partials.parametri')
    @include('fiscale.partials.clienti')

</div>
</x-app-layout>
