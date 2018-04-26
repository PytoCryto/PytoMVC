<?php

namespace PytoMVC\System\Log;

use PytoMVC\Core\App;
use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;

/**
 * @see https://github.com/illuminate/log/tree/5.3
 */
class LoggingBootstrapper
{
    /**
     * Bootstrap the given application.
     *
     * @param  \PytoMVC\Core\App $app
     * @return void
     */
    public function bootstrap(App $app)
    {
        $log = $this->registerLogger($app);

        // If a custom Monolog configurator has been registered for the application
        // we will call that, passing Monolog along. Otherwise, we will grab the
        // the configurations for the log system and use it for configuration.
        if ($app->hasMonologConfigurator()) {
            call_user_func(
                $app->getMonologConfigurator(), $log->getMonolog()
            );
        } else {
            $this->configureHandlers($app, $log);
        }
    }

    /**
     * Register the logger instance in the container.
     *
     * @param  \PytoMVC\Core\App $app
     * @return \Illuminate\Log\Writer
     */
    protected function registerLogger(App $app)
    {
        $app->instance('log', $log = new Writer(
            new Monolog('local'), $app['events'])
        );

        return $log;
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Log\Writer $log
     * @return void
     */
    protected function configureHandlers(App $app, Writer $log)
    {
        $method = 'configure'.ucfirst($app['config']->get('app.log', 'single')).'Handler';

        $this->{$method}($app, $log);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Log\Writer $log
     * @return void
     */
    protected function configureSingleHandler(App $app, Writer $log)
    {
        $log->useFiles(
            $app->storagePath().'/logs/laravel.log',
            $app->make('config')->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Log\Writer $log
     * @return void
     */
    protected function configureDailyHandler(App $app, Writer $log)
    {
        $config = $app->make('config');

        $maxFiles = $config->get('app.log_max_files');

        $log->useDailyFiles(
            $app->storagePath().'/logs/laravel.log', is_null($maxFiles) ? 5 : $maxFiles,
            $config->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Log\Writer $log
     * @return void
     */
    protected function configureSyslogHandler(App $app, Writer $log)
    {
        $log->useSyslog(
            'laravel',
            $app->make('config')->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Log\Writer $log
     * @return void
     */
    protected function configureErrorlogHandler(App $app, Writer $log)
    {
        $log->useErrorLog($app->make('config')->get('app.log_level', 'debug'));
    }
}
