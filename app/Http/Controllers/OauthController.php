<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationStatus;
use App\Enums\Provider;
use App\Models\Integration;
use App\Models\OauthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;


class OauthController extends Controller
{
    // ---------- YANDEX ----------
    public function yandexRedirect(Request $request, string $scope)
    {
        $clientId = config('services.yandex.client_id');
        $redirect = config('services.yandex.redirect');
        $state    = Str::uuid()->toString();

        $request->session()->put('oauth.state', $state);

        // определяем провайдера
        $provider = $scope === 'metrika' ? \App\Enums\Provider::METRIKA->value : \App\Enums\Provider::DIRECT->value;
        $request->session()->put('oauth.provider', $provider);

        // ВАЖНО:
        // - Для Метрики можно явно запросить 'metrika:read'
        // - Для Директа НЕ передаём scope — Яндекс выдаст права по настройкам приложения
        $scopes = $scope === 'metrika' ? ['metrika:read'] : [];

        $authorizeUrl = config('services.yandex.authorize_url');
        $query = [
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirect,
            'state'         => $state,
            'force_confirm' => 1,
        ];

        if (!empty($scopes)) {
            $query['scope'] = implode(' ', $scopes);
        }

        return redirect()->away($authorizeUrl . '?' . http_build_query($query));
    }

    public function yandexCallback(Request $request)
    {
        $state = $request->session()->pull('oauth.state');
        $providerRaw = $request->session()->pull('oauth.provider'); // metrika|direct
        if (!$state || $request->get('state') !== $state) {
            return redirect()->route('filament.admin.pages.integrations')->with('danger', 'State mismatch.');
        }

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('filament.admin.pages.integrations')->with('danger', 'No auth code.');
        }

        $clientId     = Config::get('services.yandex.client_id');
        $clientSecret = Config::get('services.yandex.client_secret');
        $tokenUrl     = Config::get('services.yandex.token_url');
        $redirect     = Config::get('services.yandex.redirect');

        $resp = Http::asForm()->post($tokenUrl, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirect,
        ]);

        if (!$resp->ok()) {
            return redirect()->route('filament.admin.pages.integrations')
                ->with('danger', 'Yandex token error: '.$resp->body());
        }

        $data = $resp->json();

        // Сохраняем токен
        $user = $request->user();
        $providerEnum = Provider::from($providerRaw);

        OauthToken::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $providerEnum],
            [
                'access_token'  => Arr::get($data, 'access_token'),
                'refresh_token' => Arr::get($data, 'refresh_token'),
                'expires_at'    => now()->addSeconds((int) Arr::get($data, 'expires_in', 3600)),
                'scope'         => explode(' ', (string) Arr::get($data, 'scope', '')),
            ]
        );

        Integration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $providerEnum],
            ['status' => IntegrationStatus::CONNECTED, 'meta' => ['source' => 'yandex']]
        );

        return redirect()->route('filament.admin.pages.integrations')->with('success', 'Yandex подключён.');
    }

    // ---------- AMOCRM ----------
    public function amoRedirect(Request $request)
    {
        $data = $request->validate([
            'base_domain'   => 'required|string',
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
            'auth_code'     => 'nullable|string',
        ]);

        $user = $request->user();

        // сохраним креды пользователя
        \App\Models\AmoCrmCredential::updateOrCreate(
            ['user_id' => $user->id],
            [
                'base_domain'   => $data['base_domain'],
                'client_id'     => $data['client_id'],
                'client_secret' => $data['client_secret'],
            ]
        );

        // === Без редиректа: приватная интеграция через "Код авторизации" ===
        if (!empty($data['auth_code'])) {
            $tokenUrl = 'https://' . $data['base_domain'] . '/oauth2/access_token';

            $resp = Http::asJson()->post($tokenUrl, [
                'client_id'     => $data['client_id'],
                'client_secret' => $data['client_secret'],
                'grant_type'    => 'authorization_code',
                'code'          => $data['auth_code'],
                'redirect_uri'  => "https://numb.my-nimb.ru/oauth/amocrm/callback", // должен совпадать с карточкой!
            ]);

            if ($resp->failed()) {
                return back()->with('danger', 'Amo token error ['.$resp->status().']: '.$resp->body());
            }


            $json = $resp->json();

            \App\Models\OauthToken::updateOrCreate(
                ['user_id' => $user->id, 'provider' => \App\Enums\Provider::AMOCRM],
                [
                    'access_token'  => $json['access_token'] ?? null,
                    'refresh_token' => $json['refresh_token'] ?? null,
                    'expires_at'    => now()->addSeconds((int)($json['expires_in'] ?? 3600)),
                    'account_id'    => $data['base_domain'],
                    'scope'         => [],
                ]
            );

            \App\Models\Integration::updateOrCreate(
                ['user_id' => $user->id, 'provider' => \App\Enums\Provider::AMOCRM],
                ['status' => \App\Enums\IntegrationStatus::CONNECTED, 'meta' => ['base_domain' => $data['base_domain']]]
            );

            return redirect()->route('filament.admin.pages.integrations')->with('success', 'AmoCRM подключён (без редиректа).');
        }

        // === Публичный OAuth (на будущее), если auth_code пуст ===
        $state = Str::uuid()->toString();
        $request->session()->put('oauth.state', $state);
        $request->session()->put('oauth.amocrm_domain', $data['base_domain']);
        $request->session()->put('oauth.amocrm_client_id', $data['client_id']);

        $authorizeUrl = 'https://' . $data['base_domain'] . '/oauth';
        $query = http_build_query([
            'client_id'     => $data['client_id'],
            'state'         => $state,
            'redirect_uri'  => config('services.amocrm.redirect'),
            'response_type' => 'code',
        ]);

        return redirect()->away($authorizeUrl.'?'.$query);
    }



    public function amoCallback(Request $request)
    {
        $state = $request->session()->pull('oauth.state');
        if (!$state || $request->get('state') !== $state) {
            return redirect()->route('filament.admin.pages.integrations')->with('danger', 'State mismatch.');
        }

        $code = $request->get('code');
        $user = $request->user();
        $creds = \App\Models\AmoCrmCredential::where('user_id', $user->id)->firstOrFail();

        $tokenUrl = 'https://' . $creds->base_domain . '/oauth2/access_token';
        $resp = Http::asJson()->post($tokenUrl, [
            'client_id'     => $creds->client_id,
            'client_secret' => $creds->client_secret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => config('services.amocrm.redirect'),
        ])->throw()->json();

        \App\Models\OauthToken::updateOrCreate(
            ['user_id' => $user->id, 'provider' => \App\Enums\Provider::AMOCRM],
            [
                'access_token'  => $resp['access_token'] ?? null,
                'refresh_token' => $resp['refresh_token'] ?? null,
                'expires_at'    => now()->addSeconds((int)($resp['expires_in'] ?? 3600)),
                'account_id'    => $creds->base_domain, // чтобы знать портал
                'scope'         => [],
            ]
        );

        \App\Models\Integration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => \App\Enums\Provider::AMOCRM],
            ['status' => \App\Enums\IntegrationStatus::CONNECTED, 'meta' => ['base_domain' => $creds->base_domain]]
        );

        // по желанию — подписаться на вебхуки
        // \Artisan::call('amo:subscribe-webhooks', ['user_id' => $user->id]);

        return redirect()->route('filament.admin.pages.integrations')->with('success', 'AmoCRM подключён.');
    }

    // ---------- DISCONNECT ----------
    public function disconnect(Request $request, string $provider)
    {
        $user = $request->user();
        $providerEnum = Provider::from($provider);

        OauthToken::where('user_id', $user->id)->where('provider', $providerEnum)->delete();
        Integration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $providerEnum],
            ['status' => IntegrationStatus::DISCONNECTED, 'meta' => null]
        );

        return back()->with('success', Str::upper($provider).' отключён.');
    }
}
