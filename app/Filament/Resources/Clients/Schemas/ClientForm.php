<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('name_first')
                    ->required(),
                TextInput::make('name_middle')
                    ->default(null),
                TextInput::make('name_last')
                    ->required(),
                TextInput::make('phone_home')
                    ->tel()
                    ->default(null),
                TextInput::make('phone_cell')
                    ->tel()
                    ->default(null),
                TextInput::make('phone_work')
                    ->tel()
                    ->default(null),
                TextInput::make('phone_fax')
                    ->tel()
                    ->default(null),
                TextInput::make('personal_street')
                    ->default(null),
                TextInput::make('personal_number')
                    ->default(null),
                TextInput::make('personal_unit')
                    ->default(null),
                TextInput::make('personal_city')
                    ->default(null),
                TextInput::make('personal_state')
                    ->default(null),
                TextInput::make('personal_postal')
                    ->default(null),
                TextInput::make('work_street')
                    ->default(null),
                TextInput::make('work_number')
                    ->default(null),
                TextInput::make('work_unit')
                    ->default(null),
                TextInput::make('work_city')
                    ->default(null),
                TextInput::make('work_state')
                    ->default(null),
                TextInput::make('work_postal')
                    ->default(null),
                DatePicker::make('birthday')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
