<?php

namespace App\Filament\Pages;

use App\Enums\IntegrationStatus;
use App\Enums\Provider;
use App\Models\Integration;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Integrations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Интеграции';
    protected static string $view = 'filament.pages.integrations';

    protected static ?string $title = 'Интеграции';

    public function getViewData(): array
    {
        $userId = Auth::id();
        $records = Integration::where('user_id', $userId)->get()->keyBy(fn($i) => $i->provider->value);

        $status = fn($p) => optional($records->get($p))->status ?? IntegrationStatus::DISCONNECTED;
        $meta   = fn($p) => optional($records->get($p))->meta;

        return [
            'metrika' => ['status' => $status(Provider::METRIKA->value), 'meta' => $meta(Provider::METRIKA->value)],
            'direct'  => ['status' => $status(Provider::DIRECT->value),  'meta' => $meta(Provider::DIRECT->value)],
            'amocrm'  => ['status' => $status(Provider::AMOCRM->value),  'meta' => $meta(Provider::AMOCRM->value)],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Документация')
                ->url('https://yandex.ru/dev/direct/', true)
                ->color('gray'),
        ];
    }
}
