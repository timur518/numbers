<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4" wire:poll.5s="refreshState">
            {{-- Статус и прогресс --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @php
                        $status = $state['status'] ?? 'idle';
                        $badgeColor = match($status) {
                            'running' => 'warning',
                            'success' => 'success',
                            'failed'  => 'danger',
                            default   => 'gray',
                        };
                        $progress = (int)($state['progress'] ?? 0);
                        $message  = $state['message'] ?? '';
                    @endphp

                    <x-filament::badge :color="$badgeColor" class="text-sm">
                        Статус: {{ ucfirst($status) }}
                    </x-filament::badge>
                    <div class="text-sm text-gray-500">
                        {{ $message }} <span class="opacity-60">· обновлено {{ \Carbon\Carbon::parse($state['updated_at'] ?? now())->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="min-w-[120px] text-right font-medium">{{ $progress }}%</div>
            </div>

            <div class="w-full h-2 bg-gray-200 rounded">
                <div class="h-2 bg-primary-600 rounded" style="width: {{ $progress }}%"></div>
            </div>

            {{-- Краткая сводка по последнему запуску (без filament stats) --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border p-3">
                    <div class="text-sm text-gray-500">Последний запуск (начало)</div>
                    <div class="text-lg font-semibold">
                        {{ $lastRun?->started_at?->format('d.m.Y H:i') ?? '—' }}
                    </div>
                </div>
                <div class="rounded-xl border p-3">
                    <div class="text-sm text-gray-500">Завершение</div>
                    <div class="text-lg font-semibold">
                        {{ $lastRun?->finished_at?->format('d.m.Y H:i') ?? '—' }}
                    </div>
                </div>
                <div class="rounded-xl border p-3">
                    <div class="text-sm text-gray-500">Кампаний (синхронизировано)</div>
                    <div class="text-lg font-semibold">
                        {{ number_format($lastRun?->campaigns_synced ?? 0, 0, ',', ' ') }}
                    </div>
                </div>
                <div class="rounded-xl border p-3">
                    <div class="text-sm text-gray-500">API Units (потрачено)</div>
                    <div class="text-lg font-semibold">
                        {{ number_format($lastRun?->api_units_used ?? 0, 0, ',', ' ') }}
                    </div>
                </div>
            </div>

            {{-- Сообщение об ошибке при fail --}}
            @if(($state['status'] ?? '') === 'failed' && !empty($state['message']))
                <x-filament::alert color="danger" icon="heroicon-m-exclamation-triangle">
                    {{ $state['message'] }}
                </x-filament::alert>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
