<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectSyncRunResource\Pages;
use App\Models\SyncRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DirectSyncRunResource extends Resource
{
    protected static ?string $model = SyncRun::class;

    protected static ?string $navigationIcon = 'heroicon-m-arrow-path';
    protected static ?string $navigationLabel = 'Синхронизации Директа';
    protected static ?string $navigationGroup = 'Интеграции';
    protected static ?int $navigationSort = 38;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'running' => 'warning',
                        'success' => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->label('Статус')
                    ->sortable(),
                Tables\Columns\TextColumn::make('campaigns_synced')->label('Кампаний')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('ads_synced')->label('Объявлений')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('api_units_used')->label('API units')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('errors_count')->label('Ошибок')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('message')->label('Сообщение')->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('started_at')->label('Начало')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->label('Окончание')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Создано')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Обновлено')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'running' => 'running',
                        'success' => 'success',
                        'failed'  => 'failed',
                        'idle'    => 'idle',
                    ])->label('Статус'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('С'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('По'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $d) => $q->whereDate('started_at', '>=', $d))
                            ->when($data['to'] ?? null, fn($q, $d) => $q->whereDate('started_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'С: '.$data['from'];
                        if ($data['to'] ?? null)   $indicators[] = 'По: '.$data['to'];
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('Подробнее')
                    ->icon('heroicon-m-eye')
                    ->modalHeading('Подробности синхронизации')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->modalContent(function (DirectSyncRun $record) {
                        $meta = json_encode($record->meta ?? [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                        $full = json_encode([
                            'status' => $record->status,
                            'message' => $record->message,
                            'campaigns_synced' => $record->campaigns_synced,
                            'ads_synced' => $record->ads_synced,
                            'api_units_used' => $record->api_units_used,
                            'errors_count' => $record->errors_count,
                        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

                        return view('filament.modals.direct-sync-details', compact('record', 'meta', 'full'));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Удалить'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDirectSyncRuns::route('/'),
        ];
    }
}
