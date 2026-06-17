<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecordActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'record_activities';
    protected static ?string $recordTitleAttribute = 'История активности';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('recordActivity')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recordActivity')
            ->defaultSort('recordActivity.record.datetime', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('recordActivity.record.datetime')
                ->label('Запись')
                ->sortable()

                ->searchable()
                ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('recordActivity.activity.name')
                    ->label('Активность')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            1 => 'Админ',
                            2 => 'Оператор',
                            3 => 'Актер',
                            4 => 'Помощник',
                            5 => 'Стажер',
                            default => 'Неизвестно',
                        };
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
}
