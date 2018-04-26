<?php

namespace PytoMVC\System\Routing\Phroute;

use PytoMVC\System\Routing\ControllerDispatcher;

class HandlerResolver implements HandlerResolverInterface
{
    protected $container;
    protected $controllerDispatcher;

    public function __construct($container)
    {
        $this->makeControllerDispatcher($container);
    }

    public function getControllerDispatcher()
    {
        return $this->controllerDispatcher;
    }

    /**
     * Create an instance of the given handler.
     *
     * @param $handler
     * @return array
     */
    public function resolve($handler)
    {
        if (is_array($handler) && is_string($handler[0])) {
           $this->controllerDispatcher->makeController($handler[0]);
        }
        
        return $this->controllerDispatcher;
    }

    /**
     * Get the controller dispatcher instance.
     *
     * @return ControllerDispatcher
     */
    public function makeControllerDispatcher($container = null)
    {
        if (is_null($this->controllerDispatcher)) {
            $this->controllerDispatcher = $container->make(ControllerDispatcher::class);
        }

        return $this->controllerDispatcher;
    }
}
