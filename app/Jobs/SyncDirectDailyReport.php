<?php

namespace App\Jobs;

use App\Models\SyncRun;
use App\Services\DirectReportImporter;
use App\Services\YandexDirectClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncDirectDailyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId, public string $from, public string $to) {}

    public function handle(YandexDirectClient $client = null): void
    {
        $client = $client ?: YandexDirectClient::forUser($this->userId);
        $run = SyncRun::create([
            'user_id' => $this->userId,
            'provider' => 'direct',
            'scope' => 'direct',
            'job' => 'report',
            'status' => 'running',
            'started_at' => now(),
            'progress' => 0,
            'message' => "Отчёт {$this->from}..{$this->to}",
        ]);

        try {
            $tsv = $client->dailyReport($this->from, $this->to);
            $importer = app(DirectReportImporter::class);
            $affected = $importer->importTsv($this->userId, $tsv);
            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'progress' => 100,
                'affected_rows' => $affected,
                'message' => "Imported {$affected} rows",
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
