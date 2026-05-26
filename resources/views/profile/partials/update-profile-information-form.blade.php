<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label :value="__('Colore tema')" />
            <div class="mt-2 flex flex-wrap gap-3">
                @foreach([
                    'indigo' => ['label' => 'Indigo',  'bg' => '#4f46e5'],
                    'blue'   => ['label' => 'Blue',    'bg' => '#2563eb'],
                    'violet' => ['label' => 'Violet',  'bg' => '#7c3aed'],
                    'emerald'=> ['label' => 'Emerald', 'bg' => '#059669'],
                    'teal'   => ['label' => 'Teal',    'bg' => '#0d9488'],
                    'rose'   => ['label' => 'Rose',    'bg' => '#e11d48'],
                ] as $value => $opts)
                    <label class="flex flex-col items-center gap-1 cursor-pointer">
                        <input type="radio" name="theme_color" value="{{ $value }}"
                               {{ old('theme_color', $user->theme_color ?? 'indigo') === $value ? 'checked' : '' }}
                               class="sr-only peer">
                        <span class="w-8 h-8 rounded-full ring-2 ring-transparent peer-checked:ring-offset-2 peer-checked:ring-gray-600 transition"
                              style="background-color: {{ $opts['bg'] }}"></span>
                        <span class="text-xs text-gray-500">{{ $opts['label'] }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('theme_color')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <script>
        document.querySelectorAll('input[name="theme_color"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.documentElement.setAttribute('data-theme', this.value);
            });
        });
    </script>
</section>
