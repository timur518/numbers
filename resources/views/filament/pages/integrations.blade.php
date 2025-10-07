<x-filament::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Yandex Metrika --}}
        <x-filament::card>
            <div class="space-y-2">
                <div class="text-lg font-medium">Яндекс Метрика</div>
                <x-filament::badge
                    :color="$metrika['status']->value === 'connected' ? 'success' : ($metrika['status']->value === 'error' ? 'danger' : 'gray')">
                    {{ strtoupper($metrika['status']->value) }}
                </x-filament::badge>
                <div class="pt-2">
                    @if($metrika['status']->value === 'connected')
                        <form method="POST" action="{{ route('integrations.disconnect','metrika') }}">
                            @csrf
                            <x-filament::button color="danger" type="submit">Отключить</x-filament::button>
                        </form>
                    @else
                        <a href="{{ route('oauth.yandex.redirect','metrika') }}">
                            <x-filament::button color="primary">Подключить</x-filament::button>
                        </a>
                    @endif
                </div>
            </div>
        </x-filament::card>

        {{-- Yandex Direct --}}
        <x-filament::card>
            <div class="space-y-2">
                <div class="text-lg font-medium">Яндекс Директ</div>
                <x-filament::badge
                    :color="$direct['status']->value === 'connected' ? 'success' : ($direct['status']->value === 'error' ? 'danger' : 'gray')">
                    {{ strtoupper($direct['status']->value) }}
                </x-filament::badge>
                <div class="pt-2">
                    @if($direct['status']->value === 'connected')
                        <form method="POST" action="{{ route('integrations.disconnect','direct') }}">
                            @csrf
                            <x-filament::button color="danger" type="submit">Отключить</x-filament::button>
                        </form>
                    @else
                        <a href="{{ route('oauth.yandex.redirect','direct') }}">
                            <x-filament::button color="primary">Подключить</x-filament::button>
                        </a>
                    @endif
                </div>
            </div>
        </x-filament::card>

        {{-- AmoCRM --}}
        <x-filament::card>
            <div class="space-y-2">
                <div class="text-lg font-medium">AmoCRM</div>
                <x-filament::badge
                    :color="$amocrm['status']->value === 'connected' ? 'success' : ($amocrm['status']->value === 'error' ? 'danger' : 'gray')">
                    {{ strtoupper($amocrm['status']->value) }}
                </x-filament::badge>
                <div class="pt-2">
                    @if($amocrm['status']->value === 'connected')
                        <form method="POST" action="{{ route('integrations.disconnect','amocrm') }}">
                            @csrf
                            <x-filament::button color="danger" type="submit">Отключить</x-filament::button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('oauth.amocrm.redirect') }}" class="space-y-3">
                            @csrf
                            <x-filament::input name="base_domain" placeholder="yourportal.amocrm.ru" value="{{ old('base_domain') }}" required />
                            <x-filament::input name="client_id" placeholder="Client ID из Amo" required />
                            <x-filament::input type="password" name="client_secret" placeholder="Client Secret из Amo" required />
                            <x-filament::button type="submit">Подключить</x-filament::button>
                        </form>
                    @endif
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
