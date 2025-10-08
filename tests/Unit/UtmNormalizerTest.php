<?php

use App\Support\UtmNormalizer;

it('normalizes utm values', function () {
    $input = [
        'utm_source' => ' GOOGLE ',
        'utm_medium' => 'CPC',
        'utm_campaign' => 'SPRING%20SALE',
        'utm_content' => null,
        'utm_term' => 'UTM_keyword',
    ];

    $normalized = UtmNormalizer::normalize($input);

    expect($normalized)->toMatchArray([
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'spring sale',
        'utm_content' => null,
        'utm_term' => 'keyword',
    ]);
});
