<?php

namespace App\Services;

use App\Enums\Provider;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Http;

class AmoClient
{
    public function __construct(private OauthToken $token) {}

    public static function forUser(int $userId): self
    {
        $t = OauthToken::where('user_id', $userId)->where('provider', Provider::AMOCRM)->firstOrFail();
        return new self($t);
    }

    public function baseUrl(): string
    {
        return 'https://' . $this->token->account_id;
    }

    public function request()
    {
        return Http::withToken($this->token->access_token)
            ->acceptJson()
            ->baseUrl($this->baseUrl());
    }
}
