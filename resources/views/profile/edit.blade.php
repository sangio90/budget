<x-app-layout>
<div class="px-4 py-6 max-w-2xl mx-auto space-y-4">

    <h1 class="text-xl font-bold text-slate-900">Impostazioni</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 px-5 py-6">

        <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('patch')

            <div>
                <p class="text-sm font-semibold text-slate-700 mb-3">Colore tema</p>
                <div class="flex flex-wrap gap-4">
                    @foreach([
                        'indigo'  => ['label' => 'Indigo',   'bg' => '#4f46e5'],
                        'blue'    => ['label' => 'Blue',     'bg' => '#2563eb'],
                        'violet'  => ['label' => 'Violet',   'bg' => '#7c3aed'],
                        'emerald' => ['label' => 'Emerald',  'bg' => '#059669'],
                        'teal'    => ['label' => 'Teal',     'bg' => '#0d9488'],
                        'rose'    => ['label' => 'Rose',     'bg' => '#e11d48'],
                    ] as $value => $opts)
                        <label class="flex flex-col items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="theme_color" value="{{ $value }}"
                                   {{ old('theme_color', $user->theme_color ?? 'indigo') === $value ? 'checked' : '' }}
                                   class="sr-only peer">
                            <span class="w-9 h-9 rounded-full ring-2 ring-transparent peer-checked:ring-offset-2 peer-checked:ring-slate-500 transition-all"
                                  style="background-color: {{ $opts['bg'] }}"></span>
                            <span class="text-xs text-slate-500">{{ $opts['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('theme_color')" />
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition-colors">
                    Salva
                </button>

                @if(session('status') === 'profile-updated')
                    <p x-data="{ show: true }"
                       x-show="show"
                       x-transition
                       x-init="setTimeout(() => show = false, 2000)"
                       class="text-sm text-slate-500">
                        Salvato.
                    </p>
                @endif
            </div>
        </form>

    </div>

</div>

<script>
    document.querySelectorAll('input[name="theme_color"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.documentElement.setAttribute('data-theme', this.value);
        });
    });
</script>
</x-app-layout>
