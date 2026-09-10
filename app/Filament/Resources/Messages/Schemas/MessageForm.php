<?php

namespace App\Filament\Resources\Messages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sender_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('recipient_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('subject')
                    ->default(null),
                Textarea::make('content')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
