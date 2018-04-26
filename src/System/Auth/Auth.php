<?php

namespace PytoMVC\System\Auth;

class Auth
{
    /**
     * @var \PytoMVC\System\Auth\AuthUser
     */
    protected static $instance;

    /**
     * Get the instance of the AuthUser class
     * 
     * @return \PytoMVC\System\Auth\AuthUser
     */
    protected static function getInstance()
    {
        if (! isset(static::$instance)) {
            static::$instance = app(AuthUser::class);
        }

        return static::$instance;
    }

    /**
     * Dynamically call methods onto the user instance
     * 
     * @param  string $method 
     * @param  array  $args 
     * @return mixed
     */
    public function __call($method, $args)
    {
        return static::getInstance()->$method(...$args);
    }

    /**
     * Dynamically call methods onto the user instance
     * 
     * @param  string $method 
     * @param  array  $args 
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return static::getInstance()->$method(...$args);
    }
}
