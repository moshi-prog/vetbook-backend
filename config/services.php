<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'philsms' => [
        'base_url' => env('PHILSMS_BASE_URL', 'https://dashboard.philsms.com/api/v3'),
        'api_token' => env('PHILSMS_API_TOKEN'),
        'sender_id' => env('PHILSMS_SENDER_ID', 'VetBook'),
        'timeout' => 15,
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY') ?: base64_decode('QVEuQWI4Uk42SkF2TjJoRUdfOE90T1VCckZyOHJKelpBel9HdHhzV0Q3Sm1pTExBMlZLV2c='),
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'timeout' => 15,
    ],

    'vapid' => [
        'public_key'  => env('VAPID_PUBLIC_KEY', 'BIATsLpjsbog2uCxg-6xvWDpWeOjw43B32Ev9BVQobRfMR3Rtf6RttdTv7F2CgpPWupxBfFANST-CrOT52IDjF0'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'GdOoudWCbmRFBeQWmrvKNeCNJKVpTQQb0lG-SowBJNQ'),
        'subject'     => env('VAPID_SUBJECT', 'mailto:vetbookbkpetaholic@gmail.com'),
    ],

];
