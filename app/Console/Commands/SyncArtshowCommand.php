<?php

namespace App\Console\Commands;

use App\Jobs\SyncArtshowArtworksJob;
use Illuminate\Console\Command;

class SyncArtshowCommand extends Command
{
    protected $signature = 'sync:artshow';

    protected $description = 'Art Show Artwork Sync';

    public function handle(): void
    {
        SyncArtshowArtworksJob::dispatchSync();
    }
}
