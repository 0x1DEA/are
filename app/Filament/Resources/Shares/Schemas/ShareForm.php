<?php

namespace App\Filament\Resources\Shares\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('listing_id')
                    ->required(),
                TextInput::make('from_name')
                    ->default(null),
                TextInput::make('from_email')
                    ->email()
                    ->default(null),
                TextInput::make('from_phone')
                    ->tel()
                    ->default(null),
                TextInput::make('to_email')
                    ->email()
                    ->default(null),
                TextInput::make('to_message')
                    ->default(null),
                TextInput::make('views')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
