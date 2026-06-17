<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartyRoomResource\Pages;
use App\Filament\Resources\PartyRoomResource\RelationManagers;
use App\Models\PartyRoom;
use App\Models\Point;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartyRoomResource extends Resource
{
    protected static ?string $model = PartyRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Випки';
    protected static ?string $label = "Випка";
    protected static ?string $pluralLabel = "Випки";
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Сервисное';
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('admin');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('point_id')
                    ->options(Point::all()->pluck('name', 'id'))
                    ->required()
                    ->label('Точка'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Название'),
                Forms\Components\TextInput::make('area')
                    ->required()
                    ->numeric()
                    ->label('Площадь (кв.м)'),
                Forms\Components\TextInput::make('price_per_hour')
                    ->required()
                    ->numeric()
                    ->label('Цена за час'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('point.name')
                    ->label('Точка')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_hour')
                    ->numeric()
                    ->label('Цена за час')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartyRooms::route('/'),
            'create' => Pages\CreatePartyRoom::route('/create'),
            'edit' => Pages\EditPartyRoom::route('/{record}/edit'),
        ];
    }
}
