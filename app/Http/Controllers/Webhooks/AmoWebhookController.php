<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AmoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Amo шлёт пакет событий; можно валидировать подпись, если включишь её в интеграции.
        Log::channel('daily')->info('Amo webhook', ['payload' => $request->all()]);

        // TODO: отправь в Job разбор: обновление/создание crm_leads и crm_lead_utms.
        // dispatch(new \App\Jobs\ProcessAmoWebhook($request->all()));

        return response()->json(['ok' => true]);
    }
}
