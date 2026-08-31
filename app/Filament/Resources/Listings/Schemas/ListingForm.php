<?php

namespace App\Filament\Resources\Listings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('mls_id')
                    ->required(),
                TextInput::make('listing_agent_mls_id')
                    ->required(),
                TextInput::make('address_street')
                    ->default(null),
                TextInput::make('address_number')
                    ->default(null),
                TextInput::make('address_unit')
                    ->default(null),
                TextInput::make('address_city')
                    ->default(null),
                TextInput::make('address_state')
                    ->default(null),
                TextInput::make('address_postal')
                    ->default(null),
                TextInput::make('living_area_sq_ft')
                    ->required()
                    ->numeric(),
                TextInput::make('acres_lot')
                    ->required()
                    ->numeric(),
                TextInput::make('bedrooms')
                    ->required()
                    ->numeric(),
                TextInput::make('full_bathrooms')
                    ->required()
                    ->numeric(),
                TextInput::make('half_bathrooms')
                    ->required()
                    ->numeric(),
                TextInput::make('sales_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('real_estate_tax')
                    ->required()
                    ->numeric(),
                TextInput::make('common_charges')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('year_built')
                    ->default(null),
                TextInput::make('neighborhood')
                    ->default(null),
            ]);
    }
}
