<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \DB::extend('odbc', function($config, $name) {
            $dsn = isset($config['dsn']) && !empty($config['dsn']) ? $config['dsn'] : $config['database'];
            if (strpos($dsn, 'odbc:') !== 0) {
                $dsn = 'odbc:' . $dsn;
            }
            $user = isset($config['username']) ? $config['username'] : '';
            $pass = isset($config['password']) ? $config['password'] : '';
            $options = isset($config['options']) ? $config['options'] : [];

            $pdo = new \PDO($dsn, $user, $pass, $options);
            return new \Illuminate\Database\Connection($pdo, $config['database'], isset($config['prefix']) ? $config['prefix'] : '', $config);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
