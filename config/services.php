<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'anthropic' => [
        'key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-haiku-5-5'),
    ],

    // Text-to-speech (VoiceController::speak): Claude has no speech-synthesis
    // API, so spoken replies come from Speaktor (Transkriptor developer API).
    'speaktor' => [
        'key' => env('TRANSKRIPTOR_API_KEY'),
        'voice' => env('SPEAKTOR_VOICE_NAME', 'Amira'),
        'language' => env('SPEAKTOR_LANGUAGE', 'id-ID'),
        'speed' => env('SPEAKTOR_SPEED_RATE'),
        'emotion' => env('SPEAKTOR_EMOTION'),
    ],

];
