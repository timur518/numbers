<?php

namespace App\Filament\Widgets;

use App\Models\SyncRun;
use Filament\Actions\Action;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class DirectSyncWidget extends Widget
{
    protected static string $view = 'filament.widgets.direct-sync-widget';
    protected static ?string $pollingInterval = '5s'; // автообновление

    // Права доступа (если нужно)
    protected static ?string $heading = 'Яндекс.Директ — Синхронизация';

    public ?array $state = null;
    public ?SyncRun $lastRun = null;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $cacheKey = $this->cacheKey();
        $this->state = Cache::get($cacheKey) ?: [
            'status' => 'idle',
            'progress' => 0,
            'message' => 'Ожидание запуска',
            'updated_at' => now()->toIso8601String(),
        ];
        $this->lastRun = SyncRun::query()
            ->where('user_id', Auth::id())
            ->where('provider', 'direct')
            ->latest('id')
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label('Запустить синхронизацию')
                ->icon('heroicon-m-play')
                ->color('primary')
                ->action(function () {
                    $userId = Auth::id();
                    if (!$userId) {
                        return;
                    }

                    Artisan::call('direct:sync', [
                        '--user_id' => [$userId],
                        '--dicts' => true,
                        '--stats' => true,
                    ]);
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

    protected function cacheKey(): string
    {
        $userId = Auth::id() ?: 'guest';
        return "direct.sync.status.{$userId}";
    }
}
