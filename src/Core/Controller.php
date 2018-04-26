<?php

namespace PytoMVC\Core;

use BadMethodCallException;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Http\Response;
use PytoMVC\System\Routing\ControllerFactory;
use Illuminate\Container\Container;

abstract class Controller
{
    /**
     * @var type
     */
    protected $container;

    /**
     * @var \PytoMVC\System\View\View
     */
    protected $view;

    /**
     * @var type
     */
    protected $model;

    /**
     * @var type
     */
    protected $form;

    /**
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;

    /**
     * @var \PytoMVC\System\Http\Response
     */
    protected $response;

    public function __construct()
    {
        //
    }

    public function render($view, $data = [], $code = 200)
    {
        return response($this->getView()->render($view, $data), $code);
    }

    public function assign($key, $value = null)
    {
        return $this->getView()->assign($key, $value);
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    public function startupProcess()
    {
        // todo: add startup logic here
        return $this->boot();
    }

    protected function boot()
    {
        return $this;
    }

    /**
     * Get the view factory instance
     * 
     * @return \PytoMVC\System\View\View
     */
    protected function getView()
    {
        if (! isset($this->view)) {
            $this->view = app('view');
        }

        return $this->view;
    }

    /**
     * Get the container instance
     * 
     * @return \Illuminate\Container\Container
     */
    protected function getContainer()
    {
        if (! isset($this->container)) {
            $this->container = app();
        }

        return $this->container;
    }

    public function beforeAction() {}

    public function beforeRender() {}

    /**
     * 
     * (0.0)
     * 
     */
    public function forward()
    {
        $parse = function ($content) {
            if (strpos($content, '@') === false) {
                throw new BadMethodCallException(__METHOD__ . ' requires Controller@Method pattern.');
            }

            return explode('@', $content);
        };

        $params = [];

        if (func_num_args() == 2) {
            $params = (array)func_get_arg(1);
        }

        list($controller, $method) = is_array($arg = func_get_arg(0)) ? [$arg[0], $arg[1]] : $parse(func_get_arg(0));

        if (is_string($controller)) {
            $controller = app(ControllerFactory::class)->make($controller);
        }

        $controller->setContainer($this->container)
                    ->setRequest($this->request)
                    ->startupProcess();

        return $this->container->call([$controller, $method], $params);
    }
}
