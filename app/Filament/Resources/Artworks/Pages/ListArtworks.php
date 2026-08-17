<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Filament\Resources\Artworks\ArtworkResource;
use App\Jobs\SyncArtshowArtworksJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListArtworks extends ListRecords
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('syncArtshow')
                ->icon('heroicon-o-arrow-path')
                ->label('Sync Art Show')
                ->action(function () {
                    SyncArtshowArtworksJob::dispatch();

                    Notification::make()
                        ->title('Art show sync queued')
                        ->success()
                        ->send();
                }),
        ];
    }
}
