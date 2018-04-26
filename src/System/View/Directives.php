<?php

namespace PytoMVC\System\View;

use PytoMVC\Core\App;

class Directives
{
    /**
     * Register the directives
     * 
     * @param  \PytoMVC\Core\App         $app
     * @param  \PytoMVC\System\View\View $view 
     * @return void
     */
    public static function register(App $app, $view)
    {
        $view->directive('old', function ($expression, $compiler) {
            return $compiler->wrap("echo tplEscape(old('{$expression}'))");
        });

        $view->directive('translate', function ($expression, $compiler) {
            return $compiler->wrap("echo trans('{$expression}')");
        });

        $view->directive('route', function($expression, $compiler) {
            return $compiler->wrap("echo view()->get('url') . '/' . url()->route('{$expression}')");
        });
    }
}
