<?php

namespace App\Filament\Resources\Rooms;

use App\Filament\Resources\Rooms\RelationManagers\ScheduleEntriesRelationManager;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Rooms\Pages\ListRooms;
use App\Filament\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Resources\Rooms\Pages\EditRoom;
use App\Filament\Resources\Rooms\RelationManagers\ScreensRelationManager;
use App\Models\Room;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $slug = 'rooms';

    protected static string|\UnitEnum|null $navigationGroup = 'Programming';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                Select::make('project_id')
                    ->relationship('project', 'name'),

                TextInput::make('venue_name'),
                TextInput::make('external_id')
                    ->label('External ID')
                    ->integer()
                    ->helperText('Pretalx ID. Do not touch unless you know what you are doing!'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn(?Room $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn(?Room $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('venue_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('external_id')
                    ->label('External ID')
                    ->searchable()
                    ->sortable(),
            ])->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])->toolbarActions([
                DeleteBulkAction::make()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ScheduleEntriesRelationManager::class,
            ScreensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
