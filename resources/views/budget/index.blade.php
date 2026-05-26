<x-app-layout>
<div class="px-4 py-6 max-w-lg mx-auto space-y-5" x-data="budgetApp()">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Nuova Spesa</h1>
        <p class="text-sm text-slate-500 mt-0.5">Registra una spesa dal budget</p>
    </div>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <form method="POST" action="{{ route('budget.store') }}" class="px-5 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Categoria</label>
                <div class="relative">
                    <input type="text" x-model="catQuery"
                        @input="catOnInput()"
                        @focus="catOnFocus()"
                        @keydown.arrow-down.prevent="catMoveDown()"
                        @keydown.arrow-up.prevent="catMoveUp()"
                        @keydown.enter.prevent="catConfirm()"
                        @keydown.escape="catClose()"
                        @click.outside="catClose()"
                        placeholder="Cerca categoria..."
                        autocomplete="off"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <ul x-show="catOpen && catFiltered.length"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-auto text-sm max-h-52">
                        <template x-for="(cat, i) in catFiltered" :key="cat.value">
                            <li @mousedown.prevent="catSelect(cat)"
                                :class="i === catHighlighted ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-50'"
                                class="px-4 py-2.5 cursor-pointer"
                                x-text="cat.label"></li>
                        </template>
                    </ul>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Voce di spesa</label>
                <input type="hidden" name="budget_category_id" :value="selectedItem">
                <div class="relative">
                    <input type="text" x-model="itemQuery"
                        @input="itemOnInput()"
                        @focus="itemOnFocus()"
                        @keydown.arrow-down.prevent="itemMoveDown()"
                        @keydown.arrow-up.prevent="itemMoveUp()"
                        @keydown.enter.prevent="itemConfirm()"
                        @keydown.escape="itemClose()"
                        @click.outside="itemClose()"
                        :placeholder="selectedCategoria ? 'Cerca voce di spesa...' : 'Prima seleziona una categoria'"
                        :disabled="!selectedCategoria"
                        autocomplete="off"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-50">
                    <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <ul x-show="itemOpen && itemFiltered.length"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-auto text-sm max-h-52">
                        <template x-for="(item, i) in itemFiltered" :key="item.id">
                            <li @mousedown.prevent="itemSelect(item)"
                                :class="i === itemHighlighted ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-50'"
                                class="px-4 py-2.5 cursor-pointer"
                                x-text="item.nome"></li>
                        </template>
                    </ul>
                </div>
                @error('budget_category_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Importo (€)</label>
                    <input type="number" name="importo" step="0.01" min="0.01" required placeholder="0,00"
                        value="{{ old('importo') }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('importo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Data</label>
                    <input type="date" name="data" required value="{{ old('data', now()->format('Y-m-d')) }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('data')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="relative">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Note (opzionale)</label>
                <input type="text" name="note" placeholder="Descrizione aggiuntiva..."
                    value="{{ old('note') }}"
                    x-model="noteInput"
                    @input="noteOnInput()"
                    @keydown.arrow-down.prevent="noteMoveDown()"
                    @keydown.arrow-up.prevent="noteMoveUp()"
                    @keydown.enter.prevent="noteSelectHighlighted()"
                    @keydown.escape="noteClose()"
                    @focus="noteOnInput()"
                    @click.outside="noteClose()"
                    autocomplete="off"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <ul x-show="noteOpen && noteSuggestions.length"
                    class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden text-sm">
                    <template x-for="(s, i) in noteSuggestions" :key="s">
                        <li @mousedown.prevent="noteSelect(s)"
                            :class="i === noteHighlighted ? 'bg-primary-50 text-primary-700' : 'text-slate-700 hover:bg-slate-50'"
                            class="px-4 py-2.5 cursor-pointer"
                            x-text="s"></li>
                    </template>
                </ul>
            </div>

            <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                Registra Spesa
            </button>
        </form>
    </div>

    <a href="{{ route('budget.spese') }}"
        class="flex items-center justify-center gap-2 text-sm text-primary-600 font-medium py-2 hover:underline">
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

        categorieList: @json(
            $categories->pluck('categoria')->unique()->sort()->values()
                ->map(fn($c) => ['value' => $c, 'label' => ucwords(strtolower($c))])
        ),

        noteMap: @json($notePerCategoria),

        // Category combobox
        catQuery: '',
        catOpen: false,
        catHighlighted: -1,

        get catFiltered() {
            const q = this.catQuery.trim().toLowerCase();
            if (!q) return this.categorieList;
            return this.categorieList.filter(c => c.label.toLowerCase().includes(q));
        },

        catOnInput() {
            this.catOpen = true;
            this.catHighlighted = -1;
            this.selectedCategoria = '';
            this.filteredItems = [];
            this.selectedItem = '';
            this.itemQuery = '';
        },

        catOnFocus() { this.catOpen = this.categorieList.length > 0; },
        catClose() { this.catOpen = false; this.catHighlighted = -1; },

        catSelect(cat) {
            this.catQuery = cat.label;
            this.selectedCategoria = cat.value;
            this.catClose();
            this.filterSubcategories();
            this.selectedItem = '';
            this.itemQuery = '';
            if (this.filteredItems.length > 0) {
                this.itemSelect(this.filteredItems[0]);
            }
        },

        catConfirm() {
            if (this.catHighlighted >= 0 && this.catFiltered[this.catHighlighted]) {
                this.catSelect(this.catFiltered[this.catHighlighted]);
            } else if (this.catFiltered.length === 1) {
                this.catSelect(this.catFiltered[0]);
            }
        },

        catMoveDown() {
            if (!this.catOpen) { this.catOpen = true; return; }
            this.catHighlighted = Math.min(this.catHighlighted + 1, this.catFiltered.length - 1);
        },

        catMoveUp() { this.catHighlighted = Math.max(this.catHighlighted - 1, -1); },

        // Item combobox
        itemQuery: '',
        itemOpen: false,
        itemHighlighted: -1,

        get itemFiltered() {
            const q = this.itemQuery.trim().toLowerCase();
            if (!q) return this.filteredItems;
            return this.filteredItems.filter(i => i.nome.toLowerCase().includes(q));
        },

        itemOnInput() {
            this.itemOpen = true;
            this.itemHighlighted = -1;
            this.selectedItem = '';
        },

        itemOnFocus() { this.itemOpen = this.filteredItems.length > 0; },
        itemClose() { this.itemOpen = false; this.itemHighlighted = -1; },

        itemSelect(item) {
            this.itemQuery = item.nome;
            this.selectedItem = item.id;
            this.itemClose();
            this.noteOnInput();
        },

        itemConfirm() {
            if (this.itemHighlighted >= 0 && this.itemFiltered[this.itemHighlighted]) {
                this.itemSelect(this.itemFiltered[this.itemHighlighted]);
            } else if (this.itemFiltered.length === 1) {
                this.itemSelect(this.itemFiltered[0]);
            }
        },

        itemMoveDown() {
            if (!this.itemOpen) { this.itemOpen = this.filteredItems.length > 0; return; }
            this.itemHighlighted = Math.min(this.itemHighlighted + 1, this.itemFiltered.length - 1);
        },

        itemMoveUp() { this.itemHighlighted = Math.max(this.itemHighlighted - 1, -1); },

        filterSubcategories() {
            this.filteredItems = this.allItems
                .filter(i => i.categoria === this.selectedCategoria)
                .sort((a, b) => a.nome.localeCompare(b.nome, 'it'));
        },

        // Note autocomplete
        noteInput: '{{ old('note') }}',
        noteOpen: false,
        noteHighlighted: -1,
        noteSuggestions: [],

        noteAllForItem() {
            return this.selectedItem && this.noteMap[this.selectedItem]
                ? this.noteMap[this.selectedItem]
                : [];
        },

        noteOnInput() {
            const q = this.noteInput.trim().toLowerCase();
            const all = this.noteAllForItem();
            this.noteSuggestions = q ? all.filter(n => n.toLowerCase().includes(q)) : all;
            this.noteOpen = this.noteSuggestions.length > 0;
            this.noteHighlighted = -1;
        },

        noteClose() { this.noteOpen = false; this.noteHighlighted = -1; },

        noteSelect(val) { this.noteInput = val; this.noteClose(); },

        noteSelectHighlighted() {
            if (this.noteHighlighted >= 0 && this.noteSuggestions[this.noteHighlighted]) {
                this.noteSelect(this.noteSuggestions[this.noteHighlighted]);
            }
        },

        noteMoveDown() {
            if (!this.noteOpen) { this.noteOnInput(); return; }
            this.noteHighlighted = Math.min(this.noteHighlighted + 1, this.noteSuggestions.length - 1);
        },

        noteMoveUp() { this.noteHighlighted = Math.max(this.noteHighlighted - 1, -1); },

        init() {
            const oldId = @json(old('budget_category_id') ?? session('last_category_id'));
            if (oldId) {
                const item = this.allItems.find(i => i.id == oldId);
                if (item) {
                    this.selectedCategoria = item.categoria;
                    const catObj = this.categorieList.find(c => c.value === item.categoria);
                    this.catQuery = catObj ? catObj.label : item.categoria;
                    this.filterSubcategories();
                    this.selectedItem = item.id;
                    this.itemQuery = item.nome;
                }
            }
        },
    }
}
</script>
</x-app-layout>