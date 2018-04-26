<?php

namespace PytoMVC\System\Routing;

use Illuminate\Container\Container;

class ControllerFactory
{
    /**
     * The controller namespace
     * 
     * @var string
     */
    protected $namespace = 'App\\Http\\Controllers\\';

    /**
     * The container instance
     * 
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Create a new controller factory instance
     * 
     * @param  \Illuminate\Container\Container $container 
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the controller namespace
     * 
     * @param  string $namespace 
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = rtrim($namespace, '\\') . '\\';

        return $this;
    }

    /**
     * Append data to the namespace
     * 
     * @param  string $namespace 
     * @return $this
     */
    public function appendNamespace($namespace)
    {
        $this->namespace .= rtrim($namespace, '\\') . '\\';

        return $this;
    }

    /**
     * Get the full controller name with namespace
     * 
     * @param  string $controller 
     * @return string
     */
    public function name($controller)
    {
        return $this->namespace . $controller;
    }

    /**
     * Return a new controller instance from the container
     * 
     * @param  string $controller 
     * @return object
     */
    public function make($controller)
    {
        return $this->container->make($this->name($controller));
    }
}
