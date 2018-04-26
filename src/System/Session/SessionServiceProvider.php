<?php

namespace PytoMVC\System\Session;

use Illuminate\Support\ServiceProvider;
use PytoMVC\System\Session\Session as SessionHandler;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('session', function ($app) {
            return new SessionHandler($app['config']['session']);
        });
    }
}
