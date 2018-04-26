<?php

use PytoMVC\System\Auth\Auth;
use PytoMVC\System\Routing\ResponseFactory;
use PytoMVC\System\Routing\UrlGenerator;
use PytoMVC\System\Routing\Redirector;

// override laravels env() because we don't use it YET.
function env($key, $default = null)
{
    return $default;
}

/**
 * Return a new response from the application.
 *
 * @param  string  $content
 * @param  int     $status
 * @param  array   $headers
 * @return \Symfony\Component\HttpFoundation\Response|\PytoMVC\System\Routing\ResponseFactory
 */
function response($content = '', $status = 200, array $headers = [])
{
    if ($content instanceof \Symfony\Component\HttpFoundation\Response) {
        return $content; // already a response object, somehow.. D:
    }

    $factory = app(ResponseFactory::class);

    if (func_num_args() === 0) {
        return $factory;
    }

    return $factory->make($content, $status, $headers);
}

/**
 * Generate a url for the application.
 *
 * @param  string  $path
 * @param  mixed   $parameters
 * @param  bool    $secure
 * @return \PytoMVC\System\Routing\UrlGenerator|string
 */
function url($path = null, $parameters = [], $secure = null)
{
    if (is_null($path)) {
        return app('url');
    }

    return app('url')->to($path, $parameters, $secure);
}

/**
 * Get an instance of the redirector.
 *
 * @param  string|null  $to
 * @param  int     $status
 * @param  array   $headers
 * @param  bool    $secure
 * @return \PytoMVC\System\Routing\Redirector|\PytoMVC\System\Http\RedirectResponse
 */
function redirect($to = null, $status = 302, $headers = [], $secure = null)
{
    if (is_null($to)) {
        return app(Redirector::class);
    }

    $to = str_replace('{url}', config('app.url'), $to);

    return app(Redirector::class)->to($to, $status, $headers, $secure);
}

/**
 * Render the given view
 * 
 * @param  string|null  $view 
 * @param  string|array $data 
 * @param  array|int    $code 
 * @return mixed
 */
function view($view = null, $data = [], $code = 200)
{
    /**
     * use cases:
     * view('index', 'My title goes here')
     * view('index', 'My title goes here', ['data' => 'value'])
     * or as normal:
     * view('index', ['data' => 'value']);
     */
    $factory = app('view');

    if (func_num_args() === 0) {
        return $factory;
    }

    if (is_string($data)) {
        $data = [
            'title' => $data . ' - ' . $factory->get('title')
        ];

        if (is_array($code)) {
            $data = array_merge($data, $code);
            $code = 200;
        }
    }

    return response($factory->render($view, $data), $code);
}

function app($abstract = null, array $parameters = [])
{
    $app = \PytoMVC\Core\App::getInstance();

    if (is_null($abstract)) {
        return $app;
    }

    return empty($parameters)
        ? $app->make($abstract)
        : $app->makeWith($abstract, $parameters);
}

function config($key, $default = null)
{
    return app('config')->get($key, $default);
}

function csrf_field($name = null)
{
    return app('csrf')->getTokenField($name);
}

function makeUrl($url)
{
    return str_replace('{url}', config('app.url'), $url);
}

function getClassConstants($class)
{
    return (new \ReflectionClass($class))->getConstants();
}

function loggedIn()
{
    return Auth::check();
}

function loggedOut()
{
    return ! Auth::check();
}
