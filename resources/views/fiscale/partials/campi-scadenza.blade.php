{{-- Campi del form scadenza. Parametri: $s (FiscalDeadline|null) --}}
@php $in = 'w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white'; @endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Scadenza</label>
        <input type="date" name="scadenza" required value="{{ old('scadenza', $s?->scadenza?->format('Y-m-d')) }}" class="{{ $in }}">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Tributo</label>
        <select name="tributo" required class="{{ $in }}">
            @foreach(\App\Models\FiscalDeadline::TRIBUTI as $t)
                <option value="{{ $t }}" {{ old('tributo', $s?->tributo) === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Anno di competenza</label>
        <input type="number" name="anno_competenza" required min="2000" max="2100" value="{{ old('anno_competenza', $s?->anno_competenza ?? now()->year) }}" class="{{ $in }}">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Stato</label>
        <select name="stato" required class="{{ $in }}">
            @foreach(\App\Models\FiscalDeadline::STATI as $k => $label)
                <option value="{{ $k }}" {{ old('stato', $s?->stato ?? 'da_pagare') === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <label class="block text-xs font-medium text-slate-600 mb-1.5">Descrizione</label>
    <input type="text" name="descrizione" required maxlength="255" placeholder="Es. IVA 3° trimestre, 1° acconto 2026, Saldo IVA…"
        value="{{ old('descrizione', $s?->descrizione) }}" class="{{ $in }}">
</div>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo previsto (€)</label>
        <input type="number" name="importo_previsto" step="0.01" required value="{{ old('importo_previsto', $s?->importo_previsto) }}" class="{{ $in }}">
        <p class="text-xs text-slate-400 mt-1">Negativo = credito che matura</p>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Compensato con credito (€)</label>
        <input type="number" name="compensato" step="0.01" min="0" value="{{ old('compensato', $s?->compensato) }}" placeholder="auto" class="{{ $in }}">
        <p class="text-xs text-slate-400 mt-1">Vuoto = scala il credito residuo</p>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo pagato (€)</label>
        <input type="number" name="importo_pagato" step="0.01" min="0" value="{{ old('importo_pagato', $s?->importo_pagato) }}" class="{{ $in }}">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Data pagamento</label>
        <input type="date" name="data_pagamento" value="{{ old('data_pagamento', $s?->data_pagamento?->format('Y-m-d')) }}" class="{{ $in }}">
    </div>
</div>
@isset($f24)
<div>
    <label class="block text-xs font-medium text-slate-600 mb-1.5">F24 collegato (Entrate/Uscite)</label>
    <select name="transaction_id" class="{{ $in }}">
        <option value="">— nessuno —</option>
        @foreach($f24 as $t)
            <option value="{{ $t->id }}" {{ (int) old('transaction_id', $s?->transaction_id) === $t->id ? 'selected' : '' }}>
                {{ $t->data->format('d/m/Y') }} · {{ number_format($t->importo, 2, ',', '.') }} € · {{ $t->causale }}
            </option>
        @endforeach
    </select>
</div>
@endisset
<div>
    <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
    <input type="text" name="note" maxlength="1000" value="{{ old('note', $s?->note) }}" class="{{ $in }}">
</div>
