<?php

namespace App\Jobs;

use App\Models\SyncRun;
use App\Models\YdAd;
use App\Models\YdAdGroup;
use App\Models\YdCampaign;
use App\Models\YdKeyword;
use App\Services\YandexDirect\DirectApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DirectSyncDictionariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}
    public int $timeout = 1800;

    public function handle(): void
    {
        $run = SyncRun::create([
            'status' => 'running', 'scope' => 'direct-dicts', 'started_at' => now(),
        ]);

        $this->progress($run->id, 0, 'Кампании…');

        try {
            $api = new DirectApi($this->userId);

            // 1) Кампании
            $campaignIds = [];
            $totalCampaigns = 0;
            for ($off = 0, $lim = 1000;; $off += $lim) {
                $items = $api->listCampaigns($lim, $off);
                if (!$items) break;
                $up = [];
                foreach ($items as $c) {
                    $up[] = [
                        'user_id' => $this->userId,
                        'yd_campaign_id' => (int)($c['Id'] ?? 0),
                        'name' => $c['Name'] ?? null,
                        'status' => $c['Status'] ?? null,
                        'currency' => $c['Currency'] ?? null,
                        'meta' => $c,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                    $campaignIds[] = (int)($c['Id'] ?? 0);
                }
                if ($up) {
                    YdCampaign::query()->upsert($up, ['user_id','yd_campaign_id'], ['name','status','currency','meta','updated_at']);
                    $totalCampaigns += count($up);
                }
                if (count($items) < $lim) break;
            }
            $this->progress($run->id, 25, "Кампаний: {$totalCampaigns}. Группы…");

            // 2) Группы
            $adGroupIds = [];
            $totalGroups = 0;
            $batchCampaigns = array_chunk(array_unique($campaignIds), 200);
            foreach ($batchCampaigns as $batch) {
                for ($off = 0, $lim = 1000;; $off += $lim) {
                    $items = $api->listAdGroups($batch, $lim, $off);
                    if (!$items) break;
                    $up = [];
                    foreach ($items as $g) {
                        $up[] = [
                            'user_id' => $this->userId,
                            'yd_adgroup_id' => (int)($g['Id'] ?? 0),
                            'yd_campaign_id' => (int)($g['CampaignId'] ?? 0),
                            'name' => $g['Name'] ?? null,
                            'status' => $g['Status'] ?? null,
                            'meta' => $g,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                        $adGroupIds[] = (int)($g['Id'] ?? 0);
                    }
                    if ($up) {
                        YdAdGroup::query()->upsert($up, ['user_id','yd_adgroup_id'], ['yd_campaign_id','name','status','meta','updated_at']);
                        $totalGroups += count($up);
                    }
                    if (count($items) < $lim) break;
                }
            }
            $this->progress($run->id, 50, "Групп: {$totalGroups}. Объявления…");

            // 3) Объявления
            $adIds = [];
            $totalAds = 0;
            $batchGroups = array_chunk(array_unique($adGroupIds), 200);
            foreach ($batchGroups as $batch) {
                for ($off = 0, $lim = 1000;; $off += $lim) {
                    $items = $api->listAds($batch, $lim, $off);
                    if (!$items) break;
                    $up = [];
                    foreach ($items as $a) {
                        $up[] = [
                            'user_id' => $this->userId,
                            'yd_ad_id' => (int)($a['Id'] ?? 0),
                            'yd_adgroup_id' => (int)($a['AdGroupId'] ?? 0),
                            'yd_campaign_id' => (int)($a['CampaignId'] ?? 0),
                            'status' => $a['Status'] ?? null,
                            'meta' => $a,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                        $adIds[] = (int)($a['Id'] ?? 0);
                    }
                    if ($up) {
                        YdAd::query()->upsert($up, ['user_id','yd_ad_id'], ['yd_adgroup_id','yd_campaign_id','status','meta','updated_at']);
                        $totalAds += count($up);
                    }
                    if (count($items) < $lim) break;
                }
            }
            $this->progress($run->id, 70, "Объявлений: {$totalAds}. Ключевые фразы…");

            // 4) Ключевые фразы
            $totalKeywords = 0;
            foreach ($batchGroups as $batch) {
                for ($off = 0, $lim = 1000;; $off += $lim) {
                    $items = $api->listKeywords($batch, $lim, $off);
                    if (!$items) break;
                    $up = [];
                    foreach ($items as $k) {
                        $up[] = [
                            'user_id' => $this->userId,
                            'yd_keyword_id' => (int)($k['Id'] ?? 0),
                            'yd_adgroup_id' => (int)($k['AdGroupId'] ?? 0),
                            'yd_campaign_id' => (int)($k['CampaignId'] ?? 0),
                            'text' => $k['Text'] ?? null,
                            'status' => $k['Status'] ?? null,
                            'meta' => $k,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                    if ($up) {
                        YdKeyword::query()->upsert($up, ['user_id','yd_keyword_id'], ['yd_adgroup_id','yd_campaign_id','text','status','meta','updated_at']);
                        $totalKeywords += count($up);
                    }
                    if (count($items) < $lim) break;
                }
            }

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'campaigns_synced' => $totalCampaigns,
                'ads_synced' => $totalAds,
                'message' => "OK. Groups={$totalGroups}, Keywords={$totalKeywords}",
            ]);
            $this->progress($run->id, 100, "Готово. Кампаний {$totalCampaigns}, групп {$totalGroups}, объявлений {$totalAds}, ключей {$totalKeywords}.");

        } catch (Throwable $e) {
            $run->update(['status'=>'failed','finished_at'=>now(),'message'=>$e->getMessage()]);
            $this->progress($run->id, 0, 'Ошибка: '.$e->getMessage());
            throw $e;
        }
    }

    protected function progress(int $runId, int $pct, string $msg): void
    {
        Cache::put('direct.sync.status', [
            'status' => $pct < 100 && $pct > 0 ? 'running' : ($pct === 100 ? 'success' : 'idle'),
            'run_id' => $runId,
            'progress' => $pct,
            'message' => $msg,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHour());
    }
}
