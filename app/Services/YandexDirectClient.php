<?php

namespace App\Services;

use App\Enums\Provider;
use App\Models\Integration;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Http;

class YandexDirectClient
{
    public function __construct(
        private int $userId,
        private string $accessToken,
        private ?string $clientLogin = null, // если нужно работать от имени клиента
    ) {}

    public static function forUser(int $userId): self
    {
        $token = OauthToken::where('user_id', $userId)->where('provider', Provider::DIRECT)->firstOrFail();
        $meta  = Integration::where('user_id', $userId)->where('provider', Provider::DIRECT)->value('meta') ?? [];
        $clientLogin = is_array($meta) ? ($meta['client_login'] ?? null) : null;

        return new self($userId, $token->access_token, $clientLogin);
    }

    private function headers(array $extra = []): array
    {
        $h = [
            'Authorization'    => 'Bearer '.$this->accessToken,
            'Accept-Language'  => 'ru',
        ];
        if ($this->clientLogin) $h['Client-Login'] = $this->clientLogin;
        return array_merge($h, $extra);
    }

    /** JSON API: кампании */
    public function campaigns(array $SelectionCriteria = [], array $FieldNames = ['Id','Name','Status','Currency']): array
    {
        $body = ['method' => 'get', 'params' => compact('SelectionCriteria','FieldNames')];
        $res = Http::withHeaders($this->headers())->post('https://api.direct.yandex.com/json/v5/campaigns', $body)->throw();
        return $res->json('result.Campaigns') ?? [];
    }

    /** JSON API: группы */
    public function adGroups(array $SelectionCriteria = [], array $FieldNames = ['Id','Name','CampaignId','Status']): array
    {
        $body = ['method' => 'get', 'params' => compact('SelectionCriteria','FieldNames')];
        $res = Http::withHeaders($this->headers())->post('https://api.direct.yandex.com/json/v5/adgroups', $body)->throw();
        return $res->json('result.AdGroups') ?? [];
    }

    /** JSON API: объявления */
    public function ads(array $SelectionCriteria = [], array $FieldNames = ['Id','AdGroupId','CampaignId','Status']): array
    {
        $body = ['method' => 'get', 'params' => compact('SelectionCriteria','FieldNames')];
        $res = Http::withHeaders($this->headers())->post('https://api.direct.yandex.com/json/v5/ads', $body)->throw();
        return $res->json('result.Ads') ?? [];
    }

    /** JSON API: ключевые фразы (Keywords) */
    public function keywords(array $SelectionCriteria = [], array $FieldNames = ['Id','AdGroupId','State','Keyword']): array
    {
        $body = ['method' => 'get', 'params' => compact('SelectionCriteria','FieldNames')];
        $res = Http::withHeaders($this->headers())->post('https://api.direct.yandex.com/json/v5/keywords', $body)->throw();
        return $res->json('result.Keywords') ?? [];
    }

    /**
     * Reports API (TSV): срез день×кампания×объявление×ключ
     * Поля: Date, CampaignId, AdGroupId, AdId, CriteriaId, Impressions, Clicks, Cost, Currency
     */
    public function dailyReport(string $dateFrom, string $dateTo): string
    {
        $report = [
            'ReportName'     => 'DailyAdsKeywords',
            'ReportType'     => 'CUSTOM_REPORT',
            'DateRangeType'  => 'CUSTOM_DATE',
            'Format'         => 'TSV',
            'IncludeVAT'     => 'YES',
            'IncludeDiscounts'=> 'NO',
            'FieldNames'     => [
                'Date','CampaignId','AdGroupId','AdId','CriteriaId','Impressions','Clicks','Cost','Currency'
            ],
            'SelectionCriteria' => [
                'DateFrom' => $dateFrom,
                'DateTo'   => $dateTo,
            ],
        ];

        // В Reports API тело — JSON-строка или plain text; безопасно отдать JSON
        $headers = $this->headers([
            'returnMoneyInMicros' => 'true',           // вернёт стоимость в микросах
            'processingMode'      => 'auto',
            'skipReportHeader'    => 'true',
            'skipColumnHeader'    => 'false',
            'skipReportSummary'   => 'true',
        ]);

        $res = Http::withHeaders($headers)
            ->withBody(json_encode($report, JSON_UNESCAPED_UNICODE), 'application/json; charset=utf-8')
            ->post('https://api.direct.yandex.com/json/v5/reports');

        if ($res->status() === 201 || $res->status() === 200) {
            return $res->body(); // TSV
        }

        $res->throw(); // кинет исключение с телом ответа
        return '';
    }
}
