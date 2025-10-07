<div class="space-y-6 p-2">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">ID</div>
            <div class="text-lg font-semibold">{{ $record->id }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Статус</div>
            <div class="text-lg font-semibold">{{ strtoupper($record->status) }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Сообщение</div>
            <div class="text-lg font-semibold">{{ $record->message ?? '—' }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Начало</div>
            <div class="text-lg font-semibold">{{ $record->started_at?->format('d.m.Y H:i') ?? '—' }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Окончание</div>
            <div class="text-lg font-semibold">{{ $record->finished_at?->format('d.m.Y H:i') ?? '—' }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">API units</div>
            <div class="text-lg font-semibold">{{ $record->api_units_used }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Кампаний</div>
            <div class="text-lg font-semibold">{{ $record->campaigns_synced }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Объявлений</div>
            <div class="text-lg font-semibold">{{ $record->ads_synced }}</div>
        </div>
        <div class="rounded-xl border p-3">
            <div class="text-sm text-gray-500">Ошибок</div>
            <div class="text-lg font-semibold">{{ $record->errors_count }}</div>
        </div>
    </div>

    <div class="space-y-2">
        <div class="font-medium">Meta (JSON)</div>
        <pre class="text-xs overflow-auto rounded-lg border p-3 bg-gray-50 dark:bg-gray-900 dark:text-gray-100">{{ $meta }}</pre>
    </div>

    <div class="space-y-2">
        <div class="font-medium">Полная сводка</div>
        <pre class="text-xs overflow-auto rounded-lg border p-3 bg-gray-50 dark:bg-gray-900 dark:text-gray-100">{{ $full }}</pre>
    </div>
</div>
