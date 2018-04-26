<?php

namespace PytoMVC\System\View;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTemplateEngine();
    }

    /**
     * Register the template engine.
     * 
     * @return void
     */
    public function registerTemplateEngine()
    {
        $this->app->singleton('view', function ($app) {
            $this->registerCommonValues($app, $view = $app->make(View::class));

            Directives::register($app, $view);

            return $view;
        });
    }

    /**
     * Register the configured assets paths
     * 
     * @param  \PytoMVC\Core\App         $app 
     * @param  \PytoMVC\System\View\View $view 
     * @return void
     */
    private function registerCommonValues($app, $view)
    {
        $config = $app['config'];

        foreach ($config->get('template.assets_paths', []) as $asset => $path) {
            $view->assign($asset, $path);
        }

        if (! $view->has('title')) {
            $view->assign('title', $config['app.sitename']);
        }

        $view->assign('url', $config['app.url']);

        $view->assign('route', $app['request']->path() . '/');
    }
}
