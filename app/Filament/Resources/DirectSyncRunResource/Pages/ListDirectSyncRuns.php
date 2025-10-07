<?php

namespace App\Filament\Resources\DirectSyncRunResource\Pages;

use App\Filament\Resources\DirectSyncRunResource;
use Filament\Resources\Pages\ListRecords;

class ListDirectSyncRuns extends ListRecords
{
    protected static string $resource = DirectSyncRunResource::class;

    protected function getHeaderWidgets(): array
    {
        // можно позже вывести мини-карточки
        return [];
    }
}
