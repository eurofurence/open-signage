<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Jobs\SyncArtshowArtworksJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Artworks\ArtworkResource;
use Filament\Actions;
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
                    SyncArtshowArtworksJob::dispatchSync();
                })
        ];
    }
}
