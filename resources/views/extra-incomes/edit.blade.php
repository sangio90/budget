<x-app-layout>
<div class="px-4 py-6 max-w-lg mx-auto space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ $back ?? route('extra-incomes.index') }}" class="text-slate-400 hover:text-slate-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Modifica Entrata Extra</h1>
            <p class="text-sm text-slate-500">{{ $entrata->data->format('d/m/Y') }} · {{ $entrata->categoria }}{{ $entrata->immobile ? ' · ' . $entrata->immobile : '' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <form method="POST" action="{{ route('extra-incomes.update', $entrata) }}" class="px-5 py-5 space-y-4">
            @csrf
            @method('PUT')
            @if($back)
                <input type="hidden" name="back" value="{{ $back }}">
            @endif

            @include('extra-incomes.partials.campi', ['entrata' => $entrata])

            <div class="flex gap-3 pt-1">
                <a href="{{ $back ?? route('extra-incomes.index') }}"
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
