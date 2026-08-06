<?php

namespace App\Filament\Resources\ScheduleEntries\Pages;

use App\Jobs\SyncEurofurenceScheduleJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\ScheduleEntries\ScheduleEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListScheduleEntries extends ListRecords
{
    protected static string $resource = ScheduleEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('syncSchedule')
                ->icon('heroicon-o-arrow-path')
                ->label('Sync Schedule')
                ->action(function () {
                    SyncEurofurenceScheduleJob::dispatchSync();
                })
        ];
    }
}
