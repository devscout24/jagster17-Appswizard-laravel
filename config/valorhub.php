<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend URL
    |--------------------------------------------------------------------------
    | Used to build links that land on the React app (e.g. password reset).
    */

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    /*
    |--------------------------------------------------------------------------
    | Plan limits & features
    |--------------------------------------------------------------------------
    | Product listing limits per plan slug (null = unlimited) and the plan slugs
    | that unlock each premium contractor feature.
    */

    'product_limits' => [
        'free'  => 0,
        'pro'   => 10,
        'elite' => null,
    ],

    'feature_plans' => [
        'banner'        => ['elite'],
        'custom_domain' => ['elite'],
        'custom_slug'   => ['pro', 'elite'],
    ],

];
