<x-app-layout>
<div class="px-4 py-6 max-w-3xl mx-auto space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('fiscale.index', ['anno' => $scadenza->scadenza->year]) }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Modifica Scadenza</h1>
            <p class="text-sm text-slate-500">{{ $scadenza->scadenza->format('d/m/Y') }} · {{ $scadenza->tributo }} · {{ $scadenza->descrizione }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <form method="POST" action="{{ route('fiscale.scadenze.update', $scadenza) }}" class="px-5 py-5 space-y-4">
            @csrf
            @method('PUT')

            @include('fiscale.partials.campi-scadenza', ['s' => $scadenza, 'f24' => $f24])

            @if($scadenza->chiave)
                <label class="flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="automatica" value="1" {{ ! $scadenza->manuale ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span>Lasciala aggiornare da "Genera stime" (solo se lo stato resta <em>Stimato</em>; altrimenti viene considerata modificata a mano)</span>
                </label>
            @endif

            <div class="flex gap-3 pt-1">
                <a href="{{ route('fiscale.index', ['anno' => $scadenza->scadenza->year]) }}"
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
</x-app-layout>
