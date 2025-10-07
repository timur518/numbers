<?php

namespace App\Services\YandexDirect;

use App\Models\OauthToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Обёртка над Yandex Direct API v5 (JSON) + Reports API.
 * - listCampaigns / listAdGroups / listAds / listKeywords — постраничные справочники
 * - reportDailyStats() — отчёт за период (TSV) на нужном уровне детализации
 * - парсинг заголовка Units, троттлинг при близком исчерпании
 */
class DirectApi
{
    protected string $apiBase = 'https://api.direct.yandex.com/json/v5';
    protected string $reportsBase = 'https://api.direct.yandex.com/json/v5/reports';

    public function __construct(
        protected int $userId,
        protected ?string $clientLogin = null, // нужен, если аккаунт-менеджер
        protected string $lang = 'ru'
    ) {
        $this->clientLogin ??= config('services.yandex.direct_client_login');
    }

    /* =========================== CORE =========================== */

    protected function token(): string
    {
        $token = OauthToken::query()
            ->where('user_id', $this->userId)
            ->where('provider', 'direct')
            ->orderByDesc('id')
            ->first();

        if (!$token || !$token->access_token) {
            throw new RuntimeException('Нет access_token для Яндекс.Директ');
        }
        // если хранишь зашифрованным — расшифруй:
        try {
            $maybe = decrypt($token->access_token);
            if ($maybe) return $maybe;
        } catch (\Throwable) {}
        return $token->access_token;
    }

    protected function baseHeaders(array $extra = []): array
    {
        $h = [
            'Authorization'   => 'Bearer ' . $this->token(),
            'Accept-Language' => $this->lang,
        ];
        if ($this->clientLogin) {
            $h['Client-Login'] = $this->clientLogin;
        }
        return array_merge($h, $extra);
    }

    /** Универсальный POST к JSON API v5. Возвращает ['json' => ..., 'units' => ['spent'=>, 'rest'=>, 'limit'=>]] */
    protected function post(string $methodPath, array $payload): array
    {
        $url = rtrim($this->apiBase, '/') . '/' . ltrim($methodPath, '/');

        $resp = Http::withHeaders($this->baseHeaders())
            ->timeout(120)
            ->post($url, $payload);

        $units = $this->parseUnitsHeader($resp->header('Units'));

        if ($resp->status() === 502 || $resp->status() === 500) {
            // мягкий ретрай 1 раз
            usleep(400_000);
            $resp = Http::withHeaders($this->baseHeaders())->timeout(120)->post($url, $payload);
        }

        if (!$resp->ok()) {
            throw new RuntimeException("Direct API error {$resp->status()}: " . $resp->body());
        }

        return [
            'json'  => $resp->json(),
            'units' => $units,
        ];
    }

    /** Парсинг заголовка Units вида: "Spent: 15, Rest: 485, Limit: 5000" */
    protected function parseUnitsHeader(?string $value): array
    {
        $spent = $rest = $limit = null;
        if ($value) {
            // быстрый парсер
            foreach (explode(',', $value) as $part) {
                if (str_contains($part, 'Spent')) $spent = (int) filter_var($part, FILTER_SANITIZE_NUMBER_INT);
                if (str_contains($part, 'Rest'))  $rest  = (int) filter_var($part, FILTER_SANITIZE_NUMBER_INT);
                if (str_contains($part, 'Limit')) $limit = (int) filter_var($part, FILTER_SANITIZE_NUMBER_INT);
            }
        }
        return ['spent' => $spent, 'rest' => $rest, 'limit' => $limit];
    }

    /** Небольшой троттлинг, если остаётся мало юнитов (напр., < 50) */
    protected function maybeThrottle(?int $restUnits): void
    {
        if ($restUnits !== null && $restUnits < 50) {
            usleep(300_000); // 300 ms пауза
        }
    }

    /* ======================= DICTIONARIES ======================= */

    /** Кампании (постранично) */
    public function listCampaigns(int $limit = 1000, int $offset = 0): array
    {
        $payload = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => new \stdClass(),
                'FieldNames' => ['Id', 'Name', 'Status', 'Currency'],
                'Page' => ['Limit' => $limit, 'Offset' => $offset],
            ],
        ];
        $res = $this->post('campaigns', $payload);
        $this->maybeThrottle($res['units']['rest'] ?? null);
        return $res['json']['result']['Campaigns'] ?? [];
    }

    /** Группы объявлений */
    public function listAdGroups(array $campaignIds = [], int $limit = 1000, int $offset = 0): array
    {
        $criteria = [];
        if ($campaignIds) {
            $criteria['CampaignIds'] = array_values(array_map('intval', $campaignIds));
        }
        $payload = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => $criteria ?: new \stdClass(),
                'FieldNames' => ['Id', 'CampaignId', 'Name', 'Status'],
                'Page' => ['Limit' => $limit, 'Offset' => $offset],
            ],
        ];
        $res = $this->post('adgroups', $payload);
        $this->maybeThrottle($res['units']['rest'] ?? null);
        return $res['json']['result']['AdGroups'] ?? [];
    }

    /** Объявления */
    public function listAds(array $adGroupIds = [], int $limit = 1000, int $offset = 0): array
    {
        $criteria = [];
        if ($adGroupIds) {
            $criteria['AdGroupIds'] = array_values(array_map('intval', $adGroupIds));
        }
        $payload = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => $criteria ?: new \stdClass(),
                'FieldNames' => ['Id', 'AdGroupId', 'CampaignId', 'Status'],
                'TextAdFieldNames' => ['Title', 'Title2', 'Text', 'Href'], // при необходимости
                'Page' => ['Limit' => $limit, 'Offset' => $offset],
            ],
        ];
        $res = $this->post('ads', $payload);
        $this->maybeThrottle($res['units']['rest'] ?? null);
        return $res['json']['result']['Ads'] ?? [];
    }

    /** Ключевые фразы */
    public function listKeywords(array $adGroupIds = [], int $limit = 1000, int $offset = 0): array
    {
        $criteria = [];
        if ($adGroupIds) {
            $criteria['AdGroupIds'] = array_values(array_map('intval', $adGroupIds));
        }
        $payload = [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => $criteria ?: new \stdClass(),
                'FieldNames' => ['Id', 'AdGroupId', 'CampaignId', 'Keyword', 'State'], // State ~= status
                'Page' => ['Limit' => $limit, 'Offset' => $offset],
            ],
        ];
        $res = $this->post('keywords', $payload);
        $this->maybeThrottle($res['units']['rest'] ?? null);
        // Приводим к единообразным ключам
        $items = $res['json']['result']['Keywords'] ?? [];
        foreach ($items as &$i) {
            $i['Text'] = $i['Keyword'] ?? null;
            $i['Status'] = $i['State'] ?? null;
        }
        return $items;
    }

    /* ======================== REPORTS API ======================= */

    /**
     * Отчёт (дневная статистика) в TSV. Возвращает массив строк-ассоц. массивов.
     *
     * $level: 'CAMPAIGN' | 'AD' | 'KEYWORD'
     * $dateFrom/$dateTo: 'YYYY-MM-DD'
     */
    public function reportDailyStats(string $dateFrom, string $dateTo, string $level = 'CAMPAIGN'): array
    {
        $defFields = match (Str::upper($level)) {
            'AD'      => ['Date', 'CampaignId', 'AdId', 'Impressions', 'Clicks', 'Cost'],
            'KEYWORD' => ['Date', 'CampaignId', 'CriteriaId', 'Impressions', 'Clicks', 'Cost'],
            default   => ['Date', 'CampaignId', 'Impressions', 'Clicks', 'Cost'],
        };

        $body = [
            'params' => [
                'SelectionCriteria' => [
                    'DateFrom' => $dateFrom,
                    'DateTo'   => $dateTo,
                ],
                'FieldNames'   => $defFields,
                'ReportName'   => 'DailyStats_' . $level . '_' . $dateFrom . '_' . $dateTo,
                'ReportType'   => 'CUSTOM_REPORT',
                'DateRangeType'=> 'CUSTOM_DATE',
                'Format'       => 'TSV',
                'IncludeVAT'   => 'YES',
                'IncludeDiscount' => 'YES',
            ],
        ];

        // В Reports API payload передаётся “как есть”, но важны заголовки:
        $headers = $this->baseHeaders([
            'Content-Type'  => 'application/json; charset=utf-8',
            'processingMode'=> 'auto',     // или 'parallel' при больших объёмах
            'returnMoneyInMicros' => 'true',
            'skipReportHeader' => 'true',
            'skipReportSummary'=> 'true',
            'skipColumnHeader' => 'false',
        ]);

        $resp = Http::withHeaders($headers)
            ->timeout(300) // отчёт может собираться дольше
            ->post($this->reportsBase, $body);

        $units = $this->parseUnitsHeader($resp->header('Units'));

        // 201/202 — отчёт в обработке (обычно при 'auto' уже 200)
        if ($resp->status() === 201 || $resp->status() === 202) {
            // Простой поллинг (1-2 повтор, обычно хватает)
            for ($i = 0; $i < 3; $i++) {
                usleep(600_000);
                $resp = Http::withHeaders($headers)->timeout(300)->post($this->reportsBase, $body);
                if ($resp->ok()) break;
            }
        }

        if (!$resp->ok()) {
            throw new RuntimeException("Direct Reports error {$resp->status()}: " . $resp->body());
        }

        $this->maybeThrottle($units['rest'] ?? null);

        $tsv = trim($resp->body());
        if ($tsv === '') return [];

        return $this->parseTsv($tsv);
    }

    /** Простой парсер TSV в массив ассоциативных строк */
    protected function parseTsv(string $tsv): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $tsv);
        if (!$lines || count($lines) < 2) return [];

        $header = str_getcsv(array_shift($lines), "\t");
        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') continue;
            $values = str_getcsv($line, "\t");
            $rows[] = array_combine($header, $values);
        }
        return $rows;
    }
}
