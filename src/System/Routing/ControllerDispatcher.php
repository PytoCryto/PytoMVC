<?php

namespace PytoMVC\System\Routing;

use Illuminate\Container\Container;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Http\Response;
use PytoMVC\System\Http\RedirectResponse;
use PytoMVC\System\Routing\ResponseFactory;

class ControllerDispatcher
{
    /**
     * The IoC container
     * 
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The response factory
     * 
     * @var \PytoMVC\System\Routing\ResponseFactory
     */
    protected $factory;

    /**
     * Create dispatcher instance and register the IoC container
     * 
     * @param  \Illuminate\Container\Container             $container
     * @param  \PytoMVC\System\Routing\ResponseFactory $factory
     * @return void
     */
    public function __construct(Container $container, ResponseFactory $factory)
    {
        $this->container = $container;
        $this->factory   = $factory;
    }

    /**
     * Dispatch a request to the given controller and method.
     * 
     * @param  \PytoMVC\System\Http\Request $request 
     * @param  string $controller 
     * @param  string $method 
     * @param  array|array $params 
     * @return \PytoMVC\System\Http\Response
     */
    public function dispatch(Request $request, $controller, $method, $params = [])
    {
        $controller = app(ControllerFactory::class)->make($controller);

        $controller->setContainer($this->container) // do we need it? idunno yet :I
                    ->setRequest($request)
                    ->startupProcess();

        if (($beforeAction = $controller->beforeAction()) !== null) {
            return $this->getPreparedResponse($request, $beforeAction);
        }

        $controller->beforeRender(); // dispatch this event

        return $this->getPreparedResponse(
            $request, $this->container->call([$controller, $method], $params)
        );
    }

    /**
     * Make sure to return a Response object
     * 
     * @param  Request $request 
     * @param  mixed   $response 
     * @return \PytoMVC\System\Http\Response
     */
    public function getPreparedResponse(Request $request, $response)
    {
        if (! ($response instanceof Response) && ! ($response instanceof RedirectResponse)) {
            $response = $this->factory->make($response, $code = 200, $headers = []);
        }

        // @see: http://symfony.com/doc/current/components/http_foundation.html#sending-the-response
        return $response->prepare($request);
    }
}
