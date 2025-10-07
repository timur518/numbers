<?php
use App\Http\Controllers\OauthController;
use Illuminate\Support\Facades\Route;


// --- Yandex: единый authorize с разными scopes ---
Route::middleware(['auth'])->group(function () {
    Route::get('/oauth/yandex/redirect/{scope}', [OauthController::class, 'yandexRedirect'])
        ->whereIn('scope', ['metrika','direct'])
        ->name('oauth.yandex.redirect');

    Route::get('/oauth/yandex/callback', [OauthController::class, 'yandexCallback'])
        ->name('oauth.yandex.callback');

    // AmoCRM
    Route::get('/oauth/amocrm/redirect', [OauthController::class, 'amoRedirect'])
        ->name('oauth.amocrm.redirect');
    Route::get('/oauth/amocrm/callback', [OauthController::class, 'amoCallback'])
        ->name('oauth.amocrm.callback');
    Route::post('/oauth/amocrm/redirect', [OauthController::class, 'amoRedirect'])
        ->name('oauth.amocrm.redirect');
    Route::post('/webhooks/amocrm', [\App\Http\Controllers\Webhooks\AmoWebhookController::class, 'handle']);


    // Отключение интеграций
    Route::post('/integrations/disconnect/{provider}', [OauthController::class, 'disconnect'])
        ->whereIn('provider', ['metrika','direct','amocrm'])
        ->name('integrations.disconnect');
});
