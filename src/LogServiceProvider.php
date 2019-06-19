<?php

namespace Youzu\Log;

use Event;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->registerAppLogger();
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../databases/migrations');

        $this->app['youzu.log']->listenDB();

        // 命令运行结束后记录sql
        if ($this->app->runningInConsole()) {
            $this->app->terminating(function () {
                $this->app['youzu.log']->consoleDB();
            });
        }

        Event::listen('Illuminate\Auth\Events\Authenticated', function (Authenticated $event) {
            $user = $event->user;

            $this->app['youzu.log']->setUserResolve(function () use ($user) {
                return $user;
            });
        });
    }

    public function registerAppLogger()
    {

        $this->app->singleton('youzu.log', function ($app) {
            return new Logger;
        });

        $this->app->alias('youzu.log', Logger::class);
    }

    public function provides()
    {
        return [
            'youzu.log',
        ];
    }
}
