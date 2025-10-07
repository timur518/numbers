<?php

namespace App\Console\Commands;

use App\Jobs\DirectSyncDictionariesJob;
use Illuminate\Console\Command;

class DirectSyncDictsCommand extends Command
{
    protected $signature = 'direct:sync-dicts {user_id}';
    protected $description = 'Sync Yandex Direct dictionaries (campaigns/adgroups/ads/keywords)';

    public function handle(): int
    {
        dispatch(new DirectSyncDictionariesJob((int)$this->argument('user_id')));
        $this->info('Dictionary sync dispatched.');
        return self::SUCCESS;
    }
}
