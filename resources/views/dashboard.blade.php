<x-app-layout>
<div class="px-4 py-6 max-w-2xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-baseline justify-between">
        <h1 class="text-xl font-bold text-slate-900">{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</h1>
        @php
            $totBudget = $righe->sum('budgetMensile');
            $totSpeso  = $righe->sum('speso');
            $percTot   = $totBudget > 0 ? min(100, round($totSpeso / $totBudget * 100)) : 0;
        @endphp
        <span class="text-sm text-slate-500">
            {{ number_format($totSpeso, 0, ',', '.') }} / {{ number_format($totBudget, 0, ',', '.') }} €
        </span>
    </div>

    {{-- ============================================================
         MOBILE: swipe orizzontale tra le 3 sezioni
         ============================================================ --}}
    <div class="sm:hidden"
         x-data="{
             active: 0,
             goTo(i) {
                 this.$refs.slider.scrollTo({ left: i * this.$refs.slider.offsetWidth, behavior: 'smooth' });
             },
             syncHeight() {
                 const slide = this.$refs.slider.children[this.active];
                 if (slide) this.$refs.slider.style.height = slide.offsetHeight + 'px';
             }
         }"
         x-init="$nextTick(() => syncHeight())">

        {{-- Tab-bar di navigazione --}}
        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-4">
            <button @click="goTo(0)"
                    :class="active === 0 ? 'bg-white shadow-sm text-slate-800 font-semibold' : 'text-slate-500 font-medium'"
                    class="flex-1 text-xs py-1.5 rounded-lg transition-all duration-200">
                Categorie
            </button>
            <button @click="goTo(1)"
                    :class="active === 1 ? 'bg-white shadow-sm text-slate-800 font-semibold' : 'text-slate-500 font-medium'"
                    class="flex-1 text-xs py-1.5 rounded-lg transition-all duration-200">
                Ultime spese
            </button>
            <button @click="goTo(2)"
                    :class="active === 2 ? 'bg-white shadow-sm text-slate-800 font-semibold' : 'text-slate-500 font-medium'"
                    class="flex-1 text-xs py-1.5 rounded-lg transition-all duration-200">
                Movimenti
            </button>
        </div>

        {{-- Slider --}}
        <div x-ref="slider"
             @scroll.debounce.80ms="active = Math.round($el.scrollLeft / $el.offsetWidth); syncHeight()"
             class="flex overflow-x-auto snap-x snap-mandatory items-start overflow-y-hidden"
             style="scrollbar-width: none; -ms-overflow-style: none; transition: height 0.3s ease;">

            {{-- Slide 1: Categorie --}}
            <div class="flex-shrink-0 w-full snap-start">
                <div class="grid grid-cols-1 gap-3">
                    @foreach($righe as $cat => $r)
                        @php
                            $barM  = $r['perc']     > 90 ? 'bg-red-500'   : ($r['perc']     > 70 ? 'bg-amber-400' : 'bg-emerald-500');
                            $barA  = $r['percAnno'] > 90 ? 'bg-red-400'   : ($r['percAnno'] > 70 ? 'bg-amber-300' : 'bg-emerald-400');
                            $textM = $r['perc']     > 90 ? 'text-red-500' : ($r['perc']     > 70 ? 'text-amber-500': 'text-emerald-600');
                            $url   = route('budget.spese', ['anno' => $anno, 'mese' => $mese, 'categoria' => $cat]);
                        @endphp
                        <a href="{{ $url }}"
                           class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4 hover:shadow-md hover:border-slate-200 transition-all block">
                            <div class="flex items-start justify-between mb-3">
                                <span class="text-sm font-semibold text-slate-800 leading-tight">{{ $cat }}</span>
                                <span class="text-xs font-bold {{ $textM }} ml-2 flex-shrink-0">{{ $r['perc'] }}%</span>
                            </div>
                            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                                <div class="{{ $barM }} h-2.5 rounded-full transition-all" style="width: {{ $r['perc'] }}%"></div>
                            </div>
                            <div class="flex items-baseline justify-between mb-3">
                                <span class="text-lg font-bold text-slate-800">{{ number_format($r['speso'], 0, ',', '.') }} €</span>
                                <span class="text-xs text-slate-400">/ {{ number_format($r['budgetMensile'], 0, ',', '.') }} € mese</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                                <div class="{{ $barA }} h-1.5 rounded-full transition-all" style="width: {{ $r['percAnno'] }}%"></div>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-slate-500 font-medium">{{ number_format($r['spesoAnno'], 0, ',', '.') }} €</span>
                                <span class="text-xs text-slate-400">/ {{ number_format($r['budgetAnnuale'], 0, ',', '.') }} € anno</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Slide 2: Ultime spese --}}
            <div class="flex-shrink-0 w-full snap-start">
                @if($ultimeSpese->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-50">
                        @foreach($ultimeSpese as $spesa)
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">{{ $spesa['categoria'] }}</span>
                                        <span class="text-sm font-medium text-slate-800 truncate">{{ $spesa['descrizione'] }}</span>
                                    </div>
                                    @if($spesa['note'])
                                        <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $spesa['note'] }}</p>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-sm font-bold text-red-500">− {{ number_format($spesa['importo'], 0, ',', '.') }} €</span>
                                    <p class="text-xs text-slate-400">{{ $spesa['data']->translatedFormat('d M') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400 text-center py-8">Nessuna spesa recente.</p>
                @endif
            </div>

            {{-- Slide 3: Ultimi movimenti --}}
            <div class="flex-shrink-0 w-full snap-start">
                @if($ultimiMovimenti->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-50">
                        @foreach($ultimiMovimenti as $mov)
                            @php
                                $isEntrata = $mov['tipo'] === 'entrata';
                                $segno     = $isEntrata ? '+' : '−';
                                $coloreImp = $isEntrata ? 'text-emerald-600' : 'text-red-500';
                                $badgeClass = match($mov['tipo']) {
                                    'entrata' => 'bg-emerald-100 text-emerald-700',
                                    'uscita'  => 'bg-red-100 text-red-600',
                                    'f24'     => 'bg-purple-100 text-purple-700',
                                    default   => 'bg-slate-100 text-slate-600',
                                };
                                $badgeLabel = match($mov['tipo']) {
                                    'entrata' => 'Entrata',
                                    'uscita'  => 'Uscita',
                                    'f24'     => 'F24',
                                    default   => 'Spesa',
                                };
                            @endphp
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                        <span class="text-sm font-medium text-slate-800 truncate">{{ $mov['descrizione'] }}</span>
                                    </div>
                                    @if($mov['note'])
                                        <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $mov['note'] }}</p>
                                    @endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-sm font-bold {{ $coloreImp }}">{{ $segno }} {{ number_format($mov['importo'], 0, ',', '.') }} €</span>
                                    <p class="text-xs text-slate-400">{{ $mov['data']->translatedFormat('d M') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400 text-center py-8">Nessun movimento recente.</p>
                @endif
            </div>

        </div>{{-- /slider --}}
    </div>{{-- /mobile --}}


    {{-- ============================================================
         DESKTOP: 3 sezioni verticali
         ============================================================ --}}
    <div class="hidden sm:block space-y-6">

        {{-- Categorie --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($righe as $cat => $r)
                @php
                    $barM  = $r['perc']     > 90 ? 'bg-red-500'   : ($r['perc']     > 70 ? 'bg-amber-400' : 'bg-emerald-500');
                    $barA  = $r['percAnno'] > 90 ? 'bg-red-400'   : ($r['percAnno'] > 70 ? 'bg-amber-300' : 'bg-emerald-400');
                    $textM = $r['perc']     > 90 ? 'text-red-500' : ($r['perc']     > 70 ? 'text-amber-500': 'text-emerald-600');
                    $url   = route('budget.spese', ['anno' => $anno, 'mese' => $mese, 'categoria' => $cat]);
                @endphp
                <a href="{{ $url }}"
                   class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-4 hover:shadow-md hover:border-slate-200 transition-all block">
                    <div class="flex items-start justify-between mb-3">
                        <span class="text-sm font-semibold text-slate-800 leading-tight">{{ $cat }}</span>
                        <span class="text-xs font-bold {{ $textM }} ml-2 flex-shrink-0">{{ $r['perc'] }}%</span>
                    </div>
                    <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                        <div class="{{ $barM }} h-2.5 rounded-full transition-all" style="width: {{ $r['perc'] }}%"></div>
                    </div>
                    <div class="flex items-baseline justify-between mb-3">
                        <span class="text-lg font-bold text-slate-800">{{ number_format($r['speso'], 0, ',', '.') }} €</span>
                        <span class="text-xs text-slate-400">/ {{ number_format($r['budgetMensile'], 0, ',', '.') }} € mese</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mb-1">
                        <div class="{{ $barA }} h-1.5 rounded-full transition-all" style="width: {{ $r['percAnno'] }}%"></div>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xs text-slate-500 font-medium">{{ number_format($r['spesoAnno'], 0, ',', '.') }} €</span>
                        <span class="text-xs text-slate-400">/ {{ number_format($r['budgetAnnuale'], 0, ',', '.') }} € anno</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Ultime spese --}}
        @if($ultimeSpese->isNotEmpty())
        <div>
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Ultime spese</h2>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-50">
                @foreach($ultimeSpese as $spesa)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 flex-shrink-0">{{ $spesa['categoria'] }}</span>
                                <span class="text-sm font-medium text-slate-800 truncate">{{ $spesa['descrizione'] }}</span>
                            </div>
                            @if($spesa['note'])
                                <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $spesa['note'] }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-sm font-bold text-red-500">− {{ number_format($spesa['importo'], 0, ',', '.') }} €</span>
                            <p class="text-xs text-slate-400">{{ $spesa['data']->translatedFormat('d M') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Ultimi movimenti --}}
        @if($ultimiMovimenti->isNotEmpty())
        <div>
            <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">Ultimi movimenti</h2>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-50">
                @foreach($ultimiMovimenti as $mov)
                    @php
                        $isEntrata = $mov['tipo'] === 'entrata';
                        $segno     = $isEntrata ? '+' : '−';
                        $coloreImp = $isEntrata ? 'text-emerald-600' : 'text-red-500';
                        $badgeClass = match($mov['tipo']) {
                            'entrata' => 'bg-emerald-100 text-emerald-700',
                            'uscita'  => 'bg-red-100 text-red-600',
                            'f24'     => 'bg-purple-100 text-purple-700',
                            default   => 'bg-slate-100 text-slate-600',
                        };
                        $badgeLabel = match($mov['tipo']) {
                            'entrata' => 'Entrata',
                            'uscita'  => 'Uscita',
                            'f24'     => 'F24',
                            default   => 'Spesa',
                        };
                    @endphp
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                <span class="text-sm font-medium text-slate-800 truncate">{{ $mov['descrizione'] }}</span>
                            </div>
                            @if($mov['note'])
                                <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $mov['note'] }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-sm font-bold {{ $coloreImp }}">{{ $segno }} {{ number_format($mov['importo'], 0, ',', '.') }} €</span>
                            <p class="text-xs text-slate-400">{{ $mov['data']->translatedFormat('d M') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- /desktop --}}

</div>
</x-app-layout>
