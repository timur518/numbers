<?php

namespace App\Filament\Widgets;

use App\Models\DirectSyncRun;
use Filament\Actions\Action;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DirectSyncWidget extends Widget
{
    protected static string $view = 'filament.widgets.direct-sync-widget';
    protected static ?string $pollingInterval = '5s'; // автообновление

    // Права доступа (если нужно)
    protected static ?string $heading = 'Яндекс.Директ — Синхронизация';

    public ?array $state = null;
    public ?DirectSyncRun $lastRun = null;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $this->state = Cache::get('direct.sync.status') ?: [
            'status' => 'idle',
            'progress' => 0,
            'message' => 'Ожидание запуска',
            'updated_at' => now()->toIso8601String(),
        ];
        $this->lastRun = DirectSyncRun::query()->latest('id')->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Запустить синхронизацию')
                ->icon('heroicon-m-play')
                ->color('primary')
                ->action(function () {
                    Artisan::call('direct:sync');
                    $this->refreshState();
                })
                ->disabled(fn () => ($this->state['status'] ?? 'idle') === 'running'),

            Action::make('refreshToken')
                ->label('Обновить токен')
                ->icon('heroicon-m-arrow-path')
                ->color('secondary')
                ->action(function () {
                    // у тебя уже есть команда обновления токенов
                    Artisan::call('oauth:refresh');
                    $this->refreshState();
                }),

            Action::make('reload')
                ->label('Обновить')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshState()),
        ];
    }
}
