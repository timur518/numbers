{{-- Чистый HTML/Tailwind без filament::* компонентов --}}
<div class="p-4 space-y-4" wire:poll.5s="refreshState">
    @php
        $status   = $state['status'] ?? 'idle';
        $progress = (int)($state['progress'] ?? 0);
        $message  = $state['message'] ?? 'Ожидание запуска';
        $updated  = \Carbon\Carbon::parse($state['updated_at'] ?? now())->diffForHumans();

        $badgeClasses = match($status) {
            'running' => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
            'success' => 'bg-green-100 text-green-800 ring-green-200',
            'failed'  => 'bg-red-100 text-red-800 ring-red-200',
            default   => 'bg-gray-100 text-gray-800 ring-gray-200',
        };
    @endphp

    {{-- Заголовок и статус --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset {{ $badgeClasses }}">
                Статус: {{ strtoupper($status) }}
            </span>
            <div class="text-sm text-gray-500">
                {{ $message }}
                <span class="opacity-60">· обновлено {{ $updated }}</span>
            </div>
        </div>
        <div class="min-w-[120px] text-right font-semibold">{{ $progress }}%</div>
    </div>

    {{-- Прогресс-бар --}}
    <div class="w-full h-2 bg-gray-200 rounded">
        <div class="h-2 rounded bg-blue-600" style="width: {{ $progress }}%"></div>
    </div>

    {{-- Сводка по последнему запуску --}}
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
            <div class="text-sm text-gray-500">Кампаний (синхр.)</div>
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

    {{-- Ошибка (если есть) --}}
    @if(($state['status'] ?? '') === 'failed' && !empty($state['message']))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-800">
            {{ $state['message'] }}
        </div>
    @endif
</div>
