<?php

namespace App\Support;

class UtmNormalizer
{
    public static function normalize(array $utms): array
    {
        $result = [];

        foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term'] as $key) {
            $value = $utms[$key] ?? null;
            if ($value === null) {
                $result[$key] = null;
                continue;
            }

            $normalized = trim(mb_strtolower((string) $value));
            $normalized = rawurldecode($normalized);
            $normalized = str_replace(['"', "'"], '', $normalized);
            $normalized = preg_replace('/^utm[_-]/', '', $normalized ?? '');
            $result[$key] = $normalized === '' ? null : $normalized;
        }

        return $result;
    }
}
