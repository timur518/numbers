<?php

namespace App\Jobs;

use App\Models\CrmLead;
use App\Models\CrmLeadUtm;
use App\Support\UtmNormalizer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAmoWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId, public array $payload) {}

    public function handle(): void
    {
        $leads = collect(data_get($this->payload, 'leads.add', []))
            ->merge(data_get($this->payload, 'leads.update', []))
            ->merge(data_get($this->payload, 'leads.status', []));

        foreach ($leads as $lead) {
            $this->syncLead((array) $lead);
        }
    }

    protected function syncLead(array $lead): void
    {
        $leadId = (int) ($lead['id'] ?? 0);
        if ($leadId <= 0) {
            return;
        }

        $leadModel = CrmLead::updateOrCreate(
            ['user_id' => $this->userId, 'crm_lead_id' => $leadId],
            [
                'pipeline_id' => data_get($lead, 'pipeline_id'),
                'status_id' => data_get($lead, 'status_id'),
                'name' => data_get($lead, 'name'),
                'price' => (int) data_get($lead, 'price', 0),
                'is_won' => $this->determineIsWon($lead),
                'closed_at' => $this->carbon(data_get($lead, 'closed_at')),
                'created_at_crm' => $this->carbon(data_get($lead, 'created_at')),
                'updated_at_crm' => $this->carbon(data_get($lead, 'updated_at')),
                'meta' => $lead,
            ]
        );

        $utm = $this->extractUtms($lead);

        $meta = $utm['meta'] ?? null;
        $referer = $utm['referer'] ?? null;
        $landing = $utm['landing'] ?? null;
        $utmPayload = array_intersect_key($utm, array_flip([
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        ]));

        CrmLeadUtm::updateOrCreate(
            ['user_id' => $this->userId, 'crm_lead_id' => $leadModel->id],
            [
                ...$utmPayload,
                'referer' => $referer,
                'landing' => $landing,
                'first_touch_at' => $this->carbon(data_get($lead, 'first_at', data_get($lead, 'created_at'))),
                'last_touch_at' => $this->carbon(data_get($lead, 'last_at', data_get($lead, 'updated_at'))),
                'attribution_model' => data_get($lead, 'attribution_model', 'last') ?: 'last',
                'meta' => $meta,
            ]
        );
    }

    protected function determineIsWon(array $lead): bool
    {
        if (isset($lead['is_won'])) {
            return (bool) $lead['is_won'];
        }

        $status = (int) data_get($lead, 'status_id', 0);

        return in_array($status, [142, 143], true);
    }

    protected function extractUtms(array $lead): array
    {
        $customFields = collect(data_get($lead, 'custom_fields_values', []));

        $map = $customFields
            ->mapWithKeys(function ($field) {
                $code = data_get($field, 'field_code') ?? data_get($field, 'code');
                $values = (array) data_get($field, 'values', []);
                $value = data_get($values, '0.value');
                return $code ? [strtoupper($code) => $value] : [];
            });

        $utmValues = [
            'utm_source' => data_get($lead, 'utm.source') ?? $map->get('UTM_SOURCE'),
            'utm_medium' => data_get($lead, 'utm.medium') ?? $map->get('UTM_MEDIUM'),
            'utm_campaign' => data_get($lead, 'utm.campaign') ?? $map->get('UTM_CAMPAIGN'),
            'utm_content' => data_get($lead, 'utm.content') ?? $map->get('UTM_CONTENT'),
            'utm_term' => data_get($lead, 'utm.term') ?? $map->get('UTM_TERM'),
        ];

        $normalized = UtmNormalizer::normalize($utmValues);

        return [
            ...$normalized,
            'referer' => data_get($lead, 'utm.referrer') ?? $map->get('UTM_REFERRER'),
            'landing' => data_get($lead, 'utm.landing') ?? data_get($lead, 'utm.url') ?? $map->get('UTM_LANDING'),
            'meta' => [
                'source' => $utmValues,
                'raw_custom_fields' => $customFields->toArray(),
            ],
        ];
    }

    protected function carbon($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value)->utc();
        }

        return Carbon::parse($value, 'UTC');
    }
}
