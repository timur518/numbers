<?php

namespace App\Console\Commands;

use App\Enums\Provider;
use App\Models\OauthToken;
use App\Models\AmoCrmCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class RefreshOauthTokens extends Command
{
    protected $signature = 'oauth:refresh';
    protected $description = 'Refresh OAuth tokens for Yandex & AmoCRM';

    public function handle(): int
    {
        $now = now()->addMinutes(5);

        OauthToken::query()
            ->whereNotNull('refresh_token')
            ->where('expires_at', '<', $now)
            ->chunkById(100, function ($tokens) {
                foreach ($tokens as $t) {
                    try {
                        match ($t->provider) {
                            Provider::METRIKA, Provider::DIRECT => $this->refreshYandex($t),
                            Provider::AMOCRM => $this->refreshAmo($t),
                        };
                        $this->info("Refreshed token id={$t->id} provider={$t->provider->value}");
                    } catch (\Throwable $e) {
                        $this->error("Refresh failed id={$t->id}: ".$e->getMessage());
                    }
                }
            });

        return self::SUCCESS;
    }

    protected function refreshYandex(OauthToken $t): void
    {
        $resp = Http::asForm()->post(Config::get('services.yandex.token_url'), [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $t->refresh_token,
            'client_id'     => Config::get('services.yandex.client_id'),
            'client_secret' => Config::get('services.yandex.client_secret'),
        ])->throw()->json();

        $t->access_token  = $resp['access_token'] ?? $t->access_token;
        $t->refresh_token = $resp['refresh_token'] ?? $t->refresh_token;
        $t->expires_at    = now()->addSeconds((int)($resp['expires_in'] ?? 3600));
        $t->save();
    }

    protected function refreshAmo(OauthToken $t): void
    {
        // перс. креды пользователя
        $creds = AmoCrmCredential::where('user_id', $t->user_id)->firstOrFail();

        $resp = Http::asJson()->post('https://'.$t->account_id.'/oauth2/access_token', [
            'client_id'     => $creds->client_id,
            'client_secret' => $creds->client_secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $t->refresh_token,
            'redirect_uri'  => Config::get('services.amocrm.redirect'),
        ])->throw()->json();

        $t->access_token  = $resp['access_token'] ?? $t->access_token;
        $t->refresh_token = $resp['refresh_token'] ?? $t->refresh_token;
        $t->expires_at    = now()->addSeconds((int)($resp['expires_in'] ?? 3600));
        $t->save();
    }
}
