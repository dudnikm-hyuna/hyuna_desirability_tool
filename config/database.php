<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_OBJ,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'main' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],
        
        'staging' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_STAGING', 'localhost'),
            'port' => env('DB_PORT_STAGING', '3306'),
            'database' => env('DB_DATABASE_STAGING', 'jomedia2'),
            'username' => env('DB_USERNAME_STAGING', 'root'),
            'password' => env('DB_PASSWORD_STAGING', 'root'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'jomedia2_prod' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_JOMEDIA2_PROD', 'localhost'),
            'port' => env('DB_PORT_JOMEDIA2_PROD', '3306'),
            'database' => env('DB_DATABASE_JOMEDIA2_PROD', 'jomedia2'),
            'username' => env('DB_USERNAME_JOMEDIA2_PROD', 'root'),
            'password' => env('DB_PASSWORD_JOMEDIA2_PROD', 'root'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
            'options'   => array(
                PDO::MYSQL_ATTR_SSL_CA    => '/home/jomedia_78/cert/ca-cert.pem'
            ),
        ],

        'redshift' => [
            'driver' => 'pgsql',
            'host' => env('DB_REDSHIFT_HOST', 'localhost'),
            'port' => env('DB_REDSHIFT_PORT', '5439'),
            'database' => env('DB_REDSHIFT_DATABASE', 'forge'),
            'username' => env('DB_REDSHIFT_USERNAME', 'forge'),
            'password' => env('DB_REDSHIFT_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'jomedia',
            'sslmode' => 'require',
        ],

        'redshift_prod' => [
            'driver' => 'pgsql',
            'host' => env('DB_REDSHIFT_PROD_HOST', 'localhost'),
            'port' => env('DB_REDSHIFT_PROD_PORT', '5439'),
            'database' => env('DB_REDSHIFT_PROD_DATABASE', 'forge'),
            'username' => env('DB_REDSHIFT_PROD_USERNAME', 'forge'),
            'password' => env('DB_REDSHIFT_PROD_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'jomedia',
            'sslmode' => 'require',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', 'localhost'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];
