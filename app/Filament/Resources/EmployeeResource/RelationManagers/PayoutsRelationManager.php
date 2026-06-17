<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'История выплат';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма')
                    ->required()
                    ->numeric(),

                Forms\Components\DatePicker::make('date')
                    ->label('Дата')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('comment')
                    ->label('Комментарий')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('kzt') // Формат тенге
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Возможность добавить выплату вручную через эту таблицу (забыли нажать кнопку)
                Tables\Actions\CreateAction::make()
                    ->label('Добавить выплату'),
            ])
            ->actions([
                // Возможность изменить (если опечатались в сумме)
                Tables\Actions\EditAction::make(),

                // ГЛАВНОЕ: Возможность удалить (отменить) выплату
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc'); // Свежие выплаты сверху
    }
}
