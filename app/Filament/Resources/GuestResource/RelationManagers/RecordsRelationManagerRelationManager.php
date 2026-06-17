<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordsRelationManagerRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('records')
                    ->required()
                    ->maxLength(255),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('datetime')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('point.name')
                    ->label('Точка'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->money('kzt'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'confirmed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->defaultSort('datetime', 'desc')
            ->actions([
                // Кнопка перехода к самой записи
                Tables\Actions\Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => \App\Filament\Resources\RecordResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
