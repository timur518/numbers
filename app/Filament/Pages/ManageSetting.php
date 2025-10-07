<?php

namespace App\Filament\Pages;

use App\Settings\KaidoSetting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageSetting extends SettingsPage
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = KaidoSetting::class;
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Настройки сайта';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация о сайте')->columns(1)->schema([
                    TextInput::make('site_name')
                        ->label('Название панели')
                        ->required(),
                    Toggle::make('site_active')
                        ->label('Статус сайта'),
                    Toggle::make('registration_enabled')
                        ->label('Разрешить регистрацию'),
                    Toggle::make('password_reset_enabled')
                        ->label('Разрешить сброс пароля'),
                    Toggle::make('sso_enabled')
                        ->label('Авторизация через соц сети'),
                ]),
            ]);
    }
}
