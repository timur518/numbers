<?php

namespace App\Services;

use App\Models\YdStatDaily;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DirectReportImporter
{
    public function importTsv(int $userId, string $tsv): int
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($tsv));
        if (count($lines) <= 1) return 0;

        $header = str_getcsv(array_shift($lines), "\t");
        $map = array_flip($header); // Date,CampaignId,AdGroupId,AdId,CriteriaId,Impressions,Clicks,Cost,Currency

        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') continue;
            $cols = str_getcsv($line, "\t");
            $date = Carbon::parse($cols[$map['Date']])->toDateString();

            $rows[] = [
                'user_id'        => $userId,
                'date'           => $date,
                'yd_campaign_id' => (int)$cols[$map['CampaignId']],
                'yd_ad_id'       => isset($map['AdId']) ? (int)$cols[$map['AdId']] : null,
                'yd_keyword_id'  => isset($map['CriteriaId']) ? (int)$cols[$map['CriteriaId']] : null,
                'impressions'    => (int)$cols[$map['Impressions']],
                'clicks'         => (int)$cols[$map['Clicks']],
                'cost_micros'    => (int)$cols[$map['Cost']], // returnMoneyInMicros=true
                'currency'       => $cols[$map['Currency']] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        if (empty($rows)) return 0;

        // UPSERT по уникальному индексу
        return DB::table('yd_stats_daily')->upsert(
            $rows,
            ['user_id','date','yd_campaign_id','yd_ad_id','yd_keyword_id'],
            ['impressions','clicks','cost_micros','currency','updated_at']
        );
    }
}
