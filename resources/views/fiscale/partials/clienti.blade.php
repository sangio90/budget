{{-- Regole clienti (fattore incasso → imponibile) e categorie di spesa deducibili --}}
@php $in = 'w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white'; @endphp
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ aperto: false }">
        <button type="button" @click="aperto = !aperto" class="w-full px-5 py-4 flex items-center justify-between text-left">
            <div>
                <h2 class="font-semibold text-slate-800">Clienti e fattore</h2>
                <p class="text-xs text-slate-400 mt-0.5">Imponibile = incasso / (1 + IVA − ritenuta) · default {{ number_format(1 + (float) $parametri->aliquota_iva - (float) $parametri->aliquota_ritenuta, 2, ',', '.') }}</p>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition" :class="aperto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="aperto" x-cloak class="border-t border-slate-100">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs font-medium text-slate-500 uppercase tracking-wide">
                        <th class="px-4 py-2 text-left">Cliente</th><th class="px-4 py-2 text-right">IVA</th><th class="px-4 py-2 text-right">Ritenuta</th>
                        <th class="px-4 py-2 text-right">Fattore</th><th class="px-4 py-2 text-right">Incassato {{ $anno }}</th><th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($clienti as $c)
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-700">{{ $c->causale }}@if($c->note)<div class="text-xs text-slate-400 font-normal">{{ $c->note }}</div>@endif</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $pc($c->aliquota_iva, 0) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $pc($c->aliquota_ritenuta, 0) }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-slate-800">{{ number_format($c->fattore(), 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-slate-500 whitespace-nowrap">{{ isset($consuntivo['per_cliente'][$c->causale]) ? $eu($consuntivo['per_cliente'][$c->causale]['incassato']) : '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('fiscale.clienti.destroy', $c) }}" onsubmit="return confirm('Eliminare la regola? Si userà il fattore di default.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-slate-300 hover:text-red-400 transition">elimina</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @foreach($causaliSenzaRegola as $causale)
                        <tr class="text-slate-400">
                            <td class="px-4 py-2">{{ $causale }} <span class="text-xs">(default)</span></td>
                            <td class="px-4 py-2 text-right">{{ $pc($parametri->aliquota_iva, 0) }}</td>
                            <td class="px-4 py-2 text-right">{{ $pc($parametri->aliquota_ritenuta, 0) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format(1 + (float) $parametri->aliquota_iva - (float) $parametri->aliquota_ritenuta, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">{{ isset($consuntivo['per_cliente'][$causale]) ? $eu($consuntivo['per_cliente'][$causale]['incassato']) : '—' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <form method="POST" action="{{ route('fiscale.clienti.store') }}" class="px-4 py-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-5 gap-2 items-end">
                @csrf
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cliente (causale)</label>
                    <input type="text" name="causale" required class="{{ $in }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">IVA %</label>
                    <input type="number" step="any" name="aliquota_iva" required value="22" class="{{ $in }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ritenuta %</label>
                    <input type="number" step="any" name="aliquota_ritenuta" required value="20" class="{{ $in }}">
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Note</label>
                    <input type="text" name="note" class="{{ $in }}">
                </div>
                <button type="submit" class="col-span-2 sm:col-span-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 rounded-lg transition text-sm">Salva</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ aperto: false }">
        <button type="button" @click="aperto = !aperto" class="w-full px-5 py-4 flex items-center justify-between text-left">
            <div>
                <h2 class="font-semibold text-slate-800">Costi deducibili</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Categorie di spesa dell'attività: {{ $macroCategorie->filter()->keys()->implode(', ') ?: 'nessuna' }} · più i collaboratori (uscite)
                </p>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition" :class="aperto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <form x-show="aperto" x-cloak method="POST" action="{{ route('fiscale.categorie.update') }}" class="px-5 pb-5 pt-4 border-t border-slate-100 space-y-3">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($macroCategorie as $cat => $aziendale)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="categorie[]" value="{{ $cat }}" {{ $aziendale ? 'checked' : '' }} class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        {{ $cat }}
                    </label>
                @endforeach
            </div>
            @if($consuntivo)
                <p class="text-xs text-slate-400">{{ $anno }}: spese aziendali {{ $eu($consuntivo['costi_aziendali']) }} + collaboratori {{ $eu($consuntivo['collaboratori']) }}</p>
            @endif
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2.5 rounded-xl transition text-sm">Salva categorie</button>
        </form>
    </div>
</div>
