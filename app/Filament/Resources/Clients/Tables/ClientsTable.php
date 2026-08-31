<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name_first')
                    ->searchable(),
                TextColumn::make('name_middle')
                    ->searchable(),
                TextColumn::make('name_last')
                    ->searchable(),
                TextColumn::make('phone_home')
                    ->searchable(),
                TextColumn::make('phone_cell')
                    ->searchable(),
                TextColumn::make('phone_work')
                    ->searchable(),
                TextColumn::make('phone_fax')
                    ->searchable(),
                TextColumn::make('personal_street')
                    ->searchable(),
                TextColumn::make('personal_number')
                    ->searchable(),
                TextColumn::make('personal_unit')
                    ->searchable(),
                TextColumn::make('personal_city')
                    ->searchable(),
                TextColumn::make('personal_state')
                    ->searchable(),
                TextColumn::make('personal_postal')
                    ->searchable(),
                TextColumn::make('work_street')
                    ->searchable(),
                TextColumn::make('work_number')
                    ->searchable(),
                TextColumn::make('work_unit')
                    ->searchable(),
                TextColumn::make('work_city')
                    ->searchable(),
                TextColumn::make('work_state')
                    ->searchable(),
                TextColumn::make('work_postal')
                    ->searchable(),
                TextColumn::make('birthday')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('last_seen_at')
                    ->dateTime()
                    ->sortable(),
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
