<?php

namespace App\Filament\Resources\Rooms\RelationManagers;

use App\Filament\Resources\ScheduleEntries\ScheduleEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ScheduleEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedule_entries';

    protected static ?string $relatedResource = ScheduleEntryResource::class;
}
