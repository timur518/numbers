<?php

namespace App\Console\Commands;

use App\Enums\Provider;
use App\Models\OauthToken;
use App\Services\AmoClient;
use Illuminate\Console\Command;

class AmoSubscribeWebhooks extends Command
{
    protected $signature = 'amo:subscribe-webhooks {user_id}';
    protected $description = 'Subscribe AmoCRM webhooks for a user';

    public function handle(): int
    {
        $uid = (int)$this->argument('user_id');
        $client = AmoClient::forUser($uid);

        $callback = config('app.url') . '/webhooks/amocrm';

        // AmoCRM v4: настройки подписок делаются через /api/v4/webhooks/subscribe
        $resp = $client->request()->post('/api/v4/webhooks/subscribe', [
            'destination' => $callback,
            'events' => [
                ['type' => 'lead_added'],
                ['type' => 'lead_status_changed'],
                ['type' => 'lead_updated'],
            ],
        ]);

        if ($resp->failed()) {
            $this->error($resp->body());
            return self::FAILURE;
        }

        $this->info('Subscribed webhooks for user '.$uid.' to '.$callback);
        return self::SUCCESS;
    }
}
