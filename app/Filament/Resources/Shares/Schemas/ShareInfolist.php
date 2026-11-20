<?php

namespace App\Filament\Resources\Shares\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShareInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('listing_id'),
                TextEntry::make('from_name')
                    ->placeholder('-'),
                TextEntry::make('from_email')
                    ->placeholder('-'),
                TextEntry::make('from_phone')
                    ->placeholder('-'),
                TextEntry::make('to_email')
                    ->placeholder('-'),
                TextEntry::make('to_message')
                    ->placeholder('-'),
                TextEntry::make('views')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
