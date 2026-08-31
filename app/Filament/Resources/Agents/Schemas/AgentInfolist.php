<?php

namespace App\Filament\Resources\Agents\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AgentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('name_first'),
                TextEntry::make('name_middle')
                    ->placeholder('-'),
                TextEntry::make('name_last'),
                TextEntry::make('headshot_url')
                    ->placeholder('-'),
                TextEntry::make('phone_home')
                    ->placeholder('-'),
                TextEntry::make('phone_cell')
                    ->placeholder('-'),
                TextEntry::make('phone_work')
                    ->placeholder('-'),
                TextEntry::make('phone_fax')
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('personal_street')
                    ->placeholder('-'),
                TextEntry::make('personal_number')
                    ->placeholder('-'),
                TextEntry::make('personal_unit')
                    ->placeholder('-'),
                TextEntry::make('personal_city')
                    ->placeholder('-'),
                TextEntry::make('personal_state')
                    ->placeholder('-'),
                TextEntry::make('personal_postal')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('last_seen_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
