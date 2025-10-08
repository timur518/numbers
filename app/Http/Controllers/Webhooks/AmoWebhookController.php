<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\Provider;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAmoWebhook;
use App\Models\AmoCrmCredential;
use App\Models\OauthToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AmoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::channel('daily')->info('Amo webhook', ['payload' => $payload]);

        $userId = $this->resolveUserId($payload);

        if (!$userId) {
            Log::warning('Amo webhook skipped: user not resolved', ['account' => data_get($payload, 'account')]);
            return response()->json(['ok' => false, 'reason' => 'user_not_found'], 202);
        }

        ProcessAmoWebhook::dispatch($userId, $payload);

        return response()->json(['ok' => true]);
    }

    protected function resolveUserId(array $payload): ?int
    {
        $accountId = data_get($payload, 'account_id');
        $subdomain = data_get($payload, 'account.subdomain');
        $domain = $subdomain ? $subdomain . '.amocrm.ru' : null;

        $token = OauthToken::query()
            ->where('provider', Provider::AMOCRM)
            ->when($accountId || $domain, function ($query) use ($accountId, $domain) {
                $query->where(function ($q) use ($accountId, $domain) {
                    if ($accountId) {
                        $q->orWhere('account_id', $accountId);
                    }
                    if ($domain) {
                        $q->orWhere('account_id', $domain);
                    }
                });
            })
            ->first();

        if ($token) {
            return (int) $token->user_id;
        }

        if ($domain) {
            $cred = AmoCrmCredential::query()->where('base_domain', $domain)->first();
            if ($cred) {
                return (int) $cred->user_id;
            }
        }

        return null;
    }
}
