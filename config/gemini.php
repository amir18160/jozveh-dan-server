<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | The API key for the Gemini API.
    |
    */
    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini HTTP Client
    |--------------------------------------------------------------------------
    |
    | The HTTP client to use for the Gemini API.
    |
    | Guzzle is used by default. You can use any PSR-18 client.
    |
    */
    'http_client' => null,

    /*
    |--------------------------------------------------------------------------
    | Gemini HTTP Client Factory
    |--------------------------------------------------------------------------
    |
    | The HTTP client factory to use for the Gemini API.
    |
    | By default, the factory will be discovered.
    |
    */
    'http_client_factory' => null,

    /*
    |--------------------------------------------------------------------------
    | Gemini Stream Handler
    |--------------------------------------------------------------------------
    |
    | The stream handler to use for the Gemini API.
    |
    | By default, the factory will be discovered.
    |
    */
    'stream_handler' => null,
];
