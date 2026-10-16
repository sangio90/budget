{{-- Scadenziario dell'anno con credito residuo (colonne come l'Excel) --}}
@php
    $iconaModifica = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
    $iconaElimina  = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
    $entro    = $oggi->copy()->addDays(45);
    $perData  = $righeAnno->groupBy(fn($r) => $r->scadenza->toDateString());
    $totAnno  = ['importo' => $totali->sum('importo'), 'compensato' => $totali->sum('compensato'), 'da_versare' => $totali->sum('da_versare')];
    $daVersareAperte = $righeAnno->reject->chiusa()->sum('da_versare_calc');
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ nuova: {{ $errors->hasAny(['scadenza', 'descrizione', 'importo_previsto']) ? 'true' : 'false' }} }">
    <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between flex-wrap gap-3">
        <div>
            <h2 class="font-semibold text-slate-800">Scadenziario {{ $anno }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">Scadenze che si pagano nel {{ $anno }} · ancora da versare {{ $eu($daVersareAperte, 2) }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('fiscale.scadenze.genera') }}" onsubmit="return confirm('Ricalcolare le scadenze stimate del {{ $anno }}? Le righe pagate, da pagare o modificate a mano non vengono toccate.')">
                @csrf
                <input type="hidden" name="anno" value="{{ $anno }}">
                <button type="submit" class="text-sm font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl px-4 py-2 transition">Genera / aggiorna stime {{ $anno }}</button>
            </form>
            <button type="button" @click="nuova = !nuova" class="text-sm font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2 transition">+ Scadenza</button>
        </div>
    </div>

    <form x-show="nuova" x-cloak method="POST" action="{{ route('fiscale.scadenze.store') }}" class="px-5 py-4 space-y-4 border-b border-slate-100 bg-slate-50/50">
        @csrf
        @include('fiscale.partials.campi-scadenza', ['s' => null])
        <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition text-sm">Registra scadenza</button>
    </form>

    @if($righeAnno->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Scadenza</th>
                    <th class="px-4 py-3 text-left">Tributo</th>
                    <th class="px-4 py-3 text-left">Descrizione</th>
                    <th class="px-4 py-3 text-right">Importo</th>
                    <th class="px-4 py-3 text-right">Compensato</th>
                    <th class="px-4 py-3 text-right">Da versare</th>
                    <th class="px-4 py-3 text-right">Credito residuo</th>
                    <th class="px-4 py-3 text-left">Stato</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($perData as $data => $gruppo)
                    @php $tot = $totali[$data]; @endphp
                    @foreach($gruppo as $r)
                        @php
                            $prossima = ! $r->chiusa() && $r->scadenza->between($oggi->copy()->startOfDay(), $entro);
                            $scaduta  = ! $r->chiusa() && $r->scadenza->lt($oggi->copy()->startOfDay()) && $r->da_versare_calc > 0;
                            $residuo  = $r->tributo === 'IVA' ? $r->residuo_iva : (in_array($r->tributo, \App\Models\FiscalDeadline::COMPENSABILI_IRPEF, true) ? $r->residuo_irpef : null);
                        @endphp
                        <tr class="transition {{ $prossima ? 'bg-amber-50 hover:bg-amber-100/60' : ($scaduta ? 'bg-red-50' : 'hover:bg-slate-50') }}">
                            <td class="px-4 py-2.5 {{ $prossima ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ $r->scadenza->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $r->tributo }}</td>
                            <td class="px-4 py-2.5 text-slate-700">
                                <span class="font-medium">{{ $r->descrizione }}</span>
                                @if($r->manuale)<span class="ml-1 text-xs text-slate-400" title="Modificata a mano: Genera stime non la tocca">✎</span>@endif
                                @if($r->transaction)<span class="ml-1 text-xs text-emerald-600" title="F24 del {{ $r->transaction->data->format('d/m/Y') }}">F24</span>@endif
                                @if($r->note)<div class="text-xs text-slate-400 truncate max-w-md" title="{{ $r->note }}">{{ $r->note }}</div>@endif
                            </td>
                            <td class="px-4 py-2.5 text-right {{ $r->importo_previsto < 0 ? 'text-emerald-600' : 'text-slate-700' }}">{{ $eu($r->importo_previsto, 2) }}</td>
                            <td class="px-4 py-2.5 text-right text-primary-600">{{ $r->compensato_calc > 0 ? $eu($r->compensato_calc, 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $r->da_versare_calc > 0 ? ($r->chiusa() ? 'text-slate-500' : 'text-red-500') : 'text-slate-300' }}">
                                {{ $r->da_versare_calc > 0 ? $eu($r->da_versare_calc, 2) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-slate-400">{{ $residuo !== null ? $eu($residuo, 2) : '' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $badgeStato[$r->stato] ?? 'bg-slate-100 text-slate-600' }}">{{ \App\Models\FiscalDeadline::STATI[$r->stato] ?? $r->stato }}</span>
                                @if($r->data_pagamento && $r->stato === 'pagato')<div class="text-xs text-slate-400 mt-0.5">{{ $r->data_pagamento->format('d/m/Y') }}</div>@endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="{{ route('fiscale.scadenze.edit', $r) }}" class="inline-block text-slate-300 hover:text-primary-500 transition align-middle mr-2" title="Modifica">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconaModifica }}"/></svg>
                                </a>
                                <form method="POST" action="{{ route('fiscale.scadenze.destroy', $r) }}" class="inline-block align-middle" onsubmit="return confirm('Eliminare questa scadenza?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-300 hover:text-red-400 transition" title="Elimina">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconaElimina }}"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50 text-xs font-semibold">
                        <td class="px-4 py-2 text-slate-500" colspan="3">Totale {{ $tot['data']->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right text-slate-600">{{ $eu($tot['importo'], 2) }}</td>
                        <td class="px-4 py-2 text-right text-primary-600">{{ $tot['compensato'] > 0 ? $eu($tot['compensato'], 2) : '—' }}</td>
                        <td class="px-4 py-2 text-right text-slate-800">{{ $eu($tot['da_versare'], 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-bold border-t border-slate-200">
                    <td class="px-4 py-3 text-slate-700" colspan="3">Totale {{ $anno }}</td>
                    <td class="px-4 py-3 text-right text-slate-700">{{ $eu($totAnno['importo'], 2) }}</td>
                    <td class="px-4 py-3 text-right text-primary-700">{{ $eu($totAnno['compensato'], 2) }}</td>
                    <td class="px-4 py-3 text-right text-slate-900">{{ $eu($totAnno['da_versare'], 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="px-5 py-3 text-xs text-slate-400 border-t border-slate-100">
        In giallo le scadenze dei prossimi 45 giorni. Il credito IRPEF copre INPS, IRPEF, addizionali e cedolare; il credito IVA solo l'IVA.
        "Genera stime" ricalcola solo le righe in stato <em>Stimato</em> non modificate a mano (✎).
    </p>
    @else
        <div class="px-5 py-12 text-center text-slate-400">
            <p class="text-sm">Nessuna scadenza nel {{ $anno }}. Usa "Genera / aggiorna stime {{ $anno }}" per crearle.</p>
        </div>
    @endif
</div>
