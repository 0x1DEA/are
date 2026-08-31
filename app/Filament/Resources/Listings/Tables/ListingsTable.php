<?php

namespace App\Filament\Resources\Listings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mls_id')
                    ->searchable(),
                TextColumn::make('listing_agent_mls_id')
                    ->searchable(),
                TextColumn::make('address_street')
                    ->searchable(),
                TextColumn::make('address_number')
                    ->searchable(),
                TextColumn::make('address_unit')
                    ->searchable(),
                TextColumn::make('address_city')
                    ->searchable(),
                TextColumn::make('address_state')
                    ->searchable(),
                TextColumn::make('address_postal')
                    ->searchable(),
                TextColumn::make('living_area_sq_ft')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acres_lot')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bedrooms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('full_bathrooms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('half_bathrooms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sales_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('real_estate_tax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('common_charges')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('year_built')
                    ->searchable(),
                TextColumn::make('neighborhood')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
