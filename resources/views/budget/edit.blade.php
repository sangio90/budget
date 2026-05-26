<x-app-layout>
<div class="px-4 py-6 max-w-lg mx-auto space-y-5" x-data="editApp()">

    <div class="flex items-center gap-3">
        <a href="{{ route('budget.spese') }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Modifica Spesa</h1>
            <p class="text-sm text-slate-500">{{ $expense->data->format('d/m/Y') }} · {{ $expense->category->categoria }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <form method="POST" action="{{ route('budget.update', $expense) }}" class="px-5 py-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Categoria</label>
                <select x-model="selectedCategoria" @change="filterSubcategories()"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    <option value="">-- Seleziona categoria --</option>
                    @foreach($categories->pluck('categoria')->unique() as $cat)
                        <option value="{{ $cat }}" {{ $expense->category->categoria === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Voce di spesa</label>
                <select name="budget_category_id" required x-model="selectedItem"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    <option value="">-- Seleziona voce --</option>
                    <template x-for="item in filteredItems" :key="item.id">
                        <option :value="item.id" :selected="item.id === currentItemId" x-text="item.nome"></option>
                    </template>
                </select>
                @error('budget_category_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo (€)</label>
                    <input type="number" name="importo" step="0.01" min="0.01" required
                        value="{{ old('importo', $expense->importo) }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('importo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Data</label>
                    <input type="date" name="data" required value="{{ old('data', $expense->data->format('Y-m-d')) }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('data')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
                <input type="text" name="note" value="{{ old('note', $expense->note) }}"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div class="flex gap-3 pt-1">
                <a href="{{ route('budget.spese') }}"
                    class="flex-1 text-center border border-slate-200 text-slate-600 font-semibold py-3 rounded-xl transition text-sm hover:bg-slate-50">
                    Annulla
                </a>
                <button type="submit"
                    class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                    Salva modifiche
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function editApp() {
    return {
        selectedCategoria: '{{ $expense->category->categoria }}',
        selectedItem: '{{ $expense->budget_category_id }}',
        currentItemId: {{ $expense->budget_category_id }},
        allItems: @json($categories->map(fn($c) => ['id' => $c->id, 'categoria' => $c->categoria, 'nome' => $c->nome])),
        filteredItems: [],

        init() {
            this.filteredItems = this.allItems.filter(i => i.categoria === this.selectedCategoria);
        },

        filterSubcategories() {
            this.filteredItems = this.allItems.filter(i => i.categoria === this.selectedCategoria);
            this.selectedItem = '';
            this.currentItemId = null;
        },
    }
}
</script>
</x-app-layout>
