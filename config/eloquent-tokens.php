<?php

use Bkremenovic\EloquentTokens\Drivers\DatabaseTokenDriver;
use Bkremenovic\EloquentTokens\Drivers\StatelessTokenDriver;

return [

    /*
    |-----------------------------------------------------------------------------------------------
    | Token Management Configuration
    |-----------------------------------------------------------------------------------------------
    |
    | This configuration defines how tokens are managed within the application. It includes the
    | mechanics for creating, storing, and retrieving tokens, ensuring compatibility with Eloquent's ORM.
    |
    */

    'drivers' => [

        /*
        |-----------------------------------------------------------------------
        | Supported Token Drivers
        |-----------------------------------------------------------------------
        |
        | Listed here are the token drivers the system supports. Each driver is capable of handling
        | token creation, storage, and retrieval processes, with the final aim of resolving to an
        | Eloquent model.
        |
        */

        'supported' => [
            // Stateless encryption-based token handling, relying on Laravel's encrypt/decrypt functions
            'stateless' => StatelessTokenDriver::class,

            // Persistent, database-backed token management
            // 'database' => DatabaseTokenDriver::class,
        ],

        /*
        |-------------------------------------------------------------------------------------------
        | Default Token Driver
        |-------------------------------------------------------------------------------------------
        |
        | This setting designates the primary driver to be used for token generation. It will be the
        | first choice when creating new tokens and must be selected from the 'drivers.supported' list.
        */

        'default' => 'stateless'
    ],

    /*
    |-----------------------------------------------------------------------------------------------
    | Token Query Configuration
    |-----------------------------------------------------------------------------------------------
    |
    | The 'use_all_drivers' setting determines if the token lookup should be exhaustive across all
    | drivers or only limited to the first-selected driver.
    |
    | With 'use_all_drivers' enabled, the application systematically checks each driver for the token,
    | providing a comprehensive search that is crucial when multiple drivers are in place, ensuring
    | no potential match is overlooked. This exhaustive search strategy ensures maximum compatibility
    | and is beneficial in environments where multiple token systems coexist, such as during a transition
    | period between token systems or when maintaining backward compatibility.
    |
    | If 'use_all_drivers' is disabled, the lookup stops with the first driver, saving time and system effort.
    |
    */

    'use_all_drivers' => false,

    /*
    |-----------------------------------------------------------------------------------------------
    | Database Table Names
    |-----------------------------------------------------------------------------------------------
    |
    | Defines the database table names required for this package to work.
    | The default table names are used within the migrations provided in this package by default.
    |
    */

    'blacklist_table' => 'token_blacklists', // Used to store blacklisted tokens within `StatelessTokenDriver`
    'tokens_table' => 'tokens', // Used to store all tokens within `DatabaseTokenDriver`

];
