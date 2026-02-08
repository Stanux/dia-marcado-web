<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as PagSeguro, Mailgun, Postmark, AWS and more. This file provides the
    | de facto location for this type of information, allowing packages
    | to have a conventional file to locate the various service credentials.
    |
    */

    'pagseguro' => [
        'api_url' => env('PAGSEGURO_API_URL', 'https://sandbox.api.pagseguro.com'),
        'token' => env('PAGSEGURO_TOKEN', '17118f3d-8501-4f19-8633-2344f99207515c4012ca4ad6a17b78f5e3aea21f5cf051ae-9ce2-4bb5-a907-ac43b2ea4cba'),
        'webhook_secret' => env('PAGSEGURO_WEBHOOK_SECRET', ''),
        'sandbox' => env('PAGSEGURO_SANDBOX', true),
    ],

];
