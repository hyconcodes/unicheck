<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Relying Party ID
    |--------------------------------------------------------------------------
    |
    | The RP ID is the domain of your application. It must match the domain
    | that the user is authenticated on. For local development, use 'localhost'.
    |
    */

    'rp_id' => env('WEBAUTHN_RP_ID', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Relying Party Name
    |--------------------------------------------------------------------------
    |
    | A human-readable name for the relying party. This is displayed to the
    | user during registration and authentication ceremonies.
    |
    */

    'rp_name' => env('APP_NAME', 'BioCheck'),

];
