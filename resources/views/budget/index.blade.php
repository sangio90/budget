<x-app-layout>
<div class="px-4 py-6 max-w-lg mx-auto space-y-5" x-data="budgetApp()">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Nuova Spesa</h1>
        <p class="text-sm text-slate-500 mt-0.5">Registra una spesa dal budget</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <form method="POST" action="{{ route('budget.store') }}" class="px-5 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Categoria</label>
                <select x-model="selectedCategoria" @change="filterSubcategories()"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">-- Seleziona categoria --</option>
                    @foreach($categories->pluck('categoria')->unique() as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Voce di spesa</label>
                <select name="budget_category_id" required x-model="selectedItem"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                    :disabled="!selectedCategoria">
                    <option value="">-- Seleziona voce --</option>
                    <template x-for="item in filteredItems" :key="item.id">
                        <option :value="item.id" x-text="item.nome"></option>
                    </template>
                </select>
                @error('budget_category_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo (€)</label>
                    <input type="number" name="importo" step="0.01" min="0.01" required placeholder="0,00"
                        value="{{ old('importo') }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('importo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Data</label>
                    <input type="date" name="data" required value="{{ old('data', now()->format('Y-m-d')) }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('data')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="relative" x-data="noteAc">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
                <input type="text" name="note" placeholder="Descrizione aggiuntiva..."
                    value="{{ old('note') }}"
                    x-model="noteInput"
                    @input="onInput()"
                    @keydown.arrow-down.prevent="moveDown()"
                    @keydown.arrow-up.prevent="moveUp()"
                    @keydown.enter.prevent="selectHighlighted()"
                    @keydown.escape="close()"
                    @focus="onInput()"
                    @click.outside="close()"
                    autocomplete="off"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <ul x-show="open && suggestions.length"
                    class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden text-sm">
                    <template x-for="(s, i) in suggestions" :key="s">
                        <li @mousedown.prevent="select(s)"
                            :class="i === highlighted ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50'"
                            class="px-4 py-2.5 cursor-pointer"
                            x-text="s"></li>
                    </template>
                </ul>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Registra Spesa
            </button>
        </form>
    </div>

    <a href="{{ route('budget.spese') }}"
        class="flex items-center justify-center gap-2 text-sm text-indigo-600 font-medium py-2 hover:underline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        Vedi tutte le spese →
    </a>

</div>

<script>
function budgetApp() {
    return {
        selectedCategoria: '',
        selectedItem: '',
        allItems: @json($categories->map(fn($c) => ['id' => $c->id, 'categoria' => $c->categoria, 'nome' => $c->nome])),
        filteredItems: [],

        filterSubcategories() {
            this.filteredItems = this.allItems.filter(i => i.categoria === this.selectedCategoria);
            this.selectedItem = this.filteredItems.length ? this.filteredItems[0].id : '';
        },
    }
}

const _noteMap = @json($notePerCategoria);

Alpine.data('noteAc', () => ({
    noteInput: '{{ old('note') }}',
    open: false,
    highlighted: -1,
    suggestions: [],

    get categoryId() {
        return document.querySelector('[name="budget_category_id"]')?.value || '';
    },

    allNotes() {
        const id = this.categoryId;
        return id && _noteMap[id] ? _noteMap[id] : [];
    },

    onInput() {
        const q = this.noteInput.trim().toLowerCase();
        const all = this.allNotes();
        this.suggestions = q
            ? all.filter(n => n.toLowerCase().includes(q))
            : all;
        this.open = this.suggestions.length > 0;
        this.highlighted = -1;
    },

    close() { this.open = false; this.highlighted = -1; },

    select(val) {
        this.noteInput = val;
        this.close();
    },

    selectHighlighted() {
        if (this.highlighted >= 0 && this.suggestions[this.highlighted]) {
            this.select(this.suggestions[this.highlighted]);
        }
    },

    moveDown() {
        if (!this.open) { this.onInput(); return; }
        this.highlighted = Math.min(this.highlighted + 1, this.suggestions.length - 1);
    },

    moveUp() {
        this.highlighted = Math.max(this.highlighted - 1, -1);
    },
}));
</script>
</x-app-layout>
