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

            {{-- Краткая сводка по последнему запуску --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <x-filament::stats.card
                    label="Последний запуск"
                    :value="$lastRun?->started_at?->format('d.m.Y H:i') ?? '—'"
                    description="Начало"
                    icon="heroicon-m-clock"
                />
                <x-filament::stats.card
                    label="Завершение"
                    :value="$lastRun?->finished_at?->format('d.m.Y H:i') ?? '—'"
                    description="Окончание"
                    icon="heroicon-m-check-circle"
                />
                <x-filament::stats.card
                    label="Кампаний"
                    :value="number_format($lastRun?->campaigns_synced ?? 0, 0, ',', ' ')"
                    description="Синхронизировано"
                    icon="heroicon-m-rectangle-stack"
                />
                <x-filament::stats.card
                    label="API Units"
                    :value="number_format($lastRun?->api_units_used ?? 0, 0, ',', ' ')"
                    description="Потрачено"
                    icon="heroicon-m-cpu-chip"
                />
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
