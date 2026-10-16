{{--
    Campi del form entrata extra (inserimento e modifica).
    Parametri: $entrata (ExtraIncome|null), $categorie, $immobili, $causali
--}}
@php $input = 'w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white'; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Categoria</label>
        <input type="text" name="categoria" list="ei-categorie" required autocomplete="off" placeholder="Es. Affitto, Cedole..."
            value="{{ old('categoria', $entrata?->categoria) }}" class="{{ $input }}">
        <datalist id="ei-categorie">
            @foreach($categorie as $c)<option value="{{ $c }}"></option>@endforeach
        </datalist>
        @error('categoria')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Immobile (opzionale)</label>
        <input type="text" name="immobile" list="ei-immobili" autocomplete="off" placeholder="Es. Agnadello, Ombriano..."
            value="{{ old('immobile', $entrata?->immobile) }}" class="{{ $input }}">
        <datalist id="ei-immobili">
            @foreach($immobili as $i)<option value="{{ $i }}"></option>@endforeach
        </datalist>
        @error('immobile')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo (€)</label>
        <input type="number" name="importo" step="0.01" required placeholder="0,00"
            value="{{ old('importo', $entrata?->importo) }}" class="{{ $input }}">
        <p class="text-xs text-slate-400 mt-1">Negativo per ritenute/spese</p>
        @error('importo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1.5">Data</label>
        <input type="date" name="data" required value="{{ old('data', $entrata?->data?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
            class="{{ $input }}">
        @error('data')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="block text-xs font-medium text-slate-600 mb-1.5">Causale</label>
    <input type="text" name="causale" list="ei-causali" required autocomplete="off" placeholder="Es. inquilino, BBVA conto deposito, GSE..."
        value="{{ old('causale', $entrata?->causale) }}" class="{{ $input }}">
    <datalist id="ei-causali">
        @foreach($causali as $c)<option value="{{ $c }}"></option>@endforeach
    </datalist>
    @error('causale')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>

<div>
    <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
    <input type="text" name="note" placeholder="Note aggiuntive..." value="{{ old('note', $entrata?->note) }}" class="{{ $input }}">
    @error('note')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>
