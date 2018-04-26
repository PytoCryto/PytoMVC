<?php

namespace PytoMVC\System\Cache;

use Illuminate\Cache\CacheServiceProvider as BaseCacheProvider;

class CacheServiceProvider extends BaseCacheProvider
{
    /**
     * Register the cache related console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cache', 'cache.store', 'memcached.connector',
        ];
    }
}
