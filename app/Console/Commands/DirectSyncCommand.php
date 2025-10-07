<?php

namespace App\Console\Commands;

use App\Jobs\DirectFullSyncJob;
use Illuminate\Console\Command;

class DirectSyncCommand extends Command
{
    protected $signature = 'direct:sync';
    protected $description = 'Full sync with Yandex Direct';

    public function handle(): int
    {
        dispatch(new DirectFullSyncJob());
        $this->info('Sync job dispatched.');
        return self::SUCCESS;
    }
}
