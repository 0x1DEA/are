<?php

namespace App\Filament\Resources\Agents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Schema;

class AgentForm
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
                FileUpload::make('headshot_url')
                    ->disk('public')
                    ->directory('agents_headshots')
                    ->image()
                    ->default(null),
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
                TextInput::make('type')
                    ->required(),
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
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_seen_at'),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
