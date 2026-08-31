<?php

namespace App\Filament\Resources\Listings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ListingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('mls_id'),
                TextEntry::make('listing_agent_mls_id'),
                TextEntry::make('address_street')
                    ->placeholder('-'),
                TextEntry::make('address_number')
                    ->placeholder('-'),
                TextEntry::make('address_unit')
                    ->placeholder('-'),
                TextEntry::make('address_city')
                    ->placeholder('-'),
                TextEntry::make('address_state')
                    ->placeholder('-'),
                TextEntry::make('address_postal')
                    ->placeholder('-'),
                TextEntry::make('living_area_sq_ft')
                    ->numeric(),
                TextEntry::make('acres_lot')
                    ->numeric(),
                TextEntry::make('bedrooms')
                    ->numeric(),
                TextEntry::make('full_bathrooms')
                    ->numeric(),
                TextEntry::make('half_bathrooms')
                    ->numeric(),
                TextEntry::make('sales_price')
                    ->money(),
                TextEntry::make('real_estate_tax')
                    ->numeric(),
                TextEntry::make('common_charges')
                    ->numeric(),
                TextEntry::make('type'),
                TextEntry::make('year_built')
                    ->placeholder('-'),
                TextEntry::make('neighborhood')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
