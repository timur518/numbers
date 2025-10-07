<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// авто-рефреш OAuth-токенов каждые 15 минут
Schedule::command('oauth:refresh')->everyFifteenMinutes();

// ежечасовой импорт статистики Яндекс.Директ
Schedule::command('direct:sync --days=14')->hourly()->runInBackground();

// ночной полный синк Директа
Schedule::command('direct:sync')->dailyAt('03:30')->withoutOverlapping()->onOneServer()->runInBackground();

// ночной синк статистики Директа за вчера
Schedule::command('direct:sync-stats 1 --from='.now()->subDay()->toDateString().' --to='.now()->subDay()->toDateString())
    ->dailyAt('03:40')->withoutOverlapping()->onOneServer();
