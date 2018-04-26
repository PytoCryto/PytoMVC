<?php

namespace PytoMVC\System\Routing;

use PytoMVC\System\Http\RedirectResponse;
use PytoMVC\System\Routing\UrlGenerator;
use PytoMVC\System\Session\SessionInterface;

/**
 * This file was edited to my needs.
 * This file belongs to Laravel.
 * @source: https://github.com/laravel/framework/blob/5.3/src/Illuminate/Routing/Redirector.php
 */
class Redirector
{
    /**
     * The URL generator instance.
     *
     * @var \PytoMVC\System\Routing\UrlGenerator
     */
    protected $generator;

    /**
     * The session store instance.
     *
     * @var \PytoMVC\System\Session\Session
     */
    protected $session;

    /**
     * Create a new Redirector instance.
     *
     * @param  \PytoMVC\System\Routing\UrlGenerator  $generator
     * @return void
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Create a new redirect response to the "home" route.
     *
     * @param  int  $status
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function home($status = 302)
    {
        return $this->to($this->generator->route('home'), $status);
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * @param  int    $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function back($status = 302, $headers = [], $fallback = false)
    {
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
     *
     * @param  int    $status
     * @param  array  $headers
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function refresh($status = 302, $headers = [])
    {
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function away($path, $status = 302, $headers = [])
    {
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function secure($path, $status = 302, $headers = [])
    {
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Create a new redirect response to a named route.
     *
     * @param  string  $route
     * @param  array   $parameters
     * @param  int     $status
     * @param  array   $headers
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function route($route, $parameters = [], $status = 302, $headers = [])
    {
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response.
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
        $redirect = new RedirectResponse($path, $status, $headers);

        if (isset($this->session)) {
            $redirect->setCustomSession($this->session);
        }

        $redirect->setRequest($this->generator->getRequest());

        return $redirect;
    }

    /**
     * Get the URL generator instance.
     *
     * @return \PytoMVC\System\Routing\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the active session store.
     *
     * @param  \PytoMVC\System\Session\SessionInterface  $session
     * @return void
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }
}
