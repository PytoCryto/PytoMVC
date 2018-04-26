<?php
namespace PytoMVC\System\Routing\Phroute;

use PytoMVC\System\Http\RedirectResponse;
use PytoMVC\System\Http\Response;
use PytoMVC\System\Routing\Phroute\Exception\HttpRouteNotFoundException;
use PytoMVC\System\Routing\Phroute\Exception\HttpMethodNotAllowedException;

class Dispatcher
{
    private $staticRouteMap;
    private $variableRouteData;
    private $filters;
    private $beforeAll;
    private $handlerResolver;
    private $controllerDispatcher;

    protected $app;

    public $matchedRoute;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $data
     * @param $app
     */
    public function __construct(RouteDataInterface $data, $app)
    {
        $this->staticRouteMap    = $data->getStaticRoutes();
        $this->variableRouteData = $data->getVariableRoutes();
        $this->filters           = $data->getFilters();
        $this->beforeAll         = $data->getBeforeAll();

        $this->app = $app;

        $this->handlerResolver = new HandlerResolver($app);
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed|null
     */
    public function dispatch($request)
    {
        try {
            $requestMethod = $request->getMethod();
            $controllerDispatcher = $this->handlerResolver->getControllerDispatcher();

            if (! empty($this->beforeAll) && in_array(strtolower($requestMethod), array_map('strtolower', $this->beforeAll->methods))) {
                $response = call_user_func_array($this->beforeAll->callback, [$request, $this->app]);

                if ($response !== null) {
                    $response = $controllerDispatcher->getPreparedResponse($request, $response);

                    return $response;
                }
            }

            list($handler, $filters, $vars) = $this->dispatchRoute($requestMethod, trim($request->getPathInfo(), '/'));
            list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

            if (($response = $this->dispatchFilters($beforeFilter)) !== null) {
                $response = $controllerDispatcher->getPreparedResponse($request, $response);
            } elseif (is_callable($handler)) {
                $params = array_values($vars);

                if (empty($params)) {
                    $params[] = $this->app;
                }

                if (($response = $handler(...$params)) !== null) {
                    $response = $controllerDispatcher->getPreparedResponse($request, $response);
                }
            } else {
                if (strpos($handler, '@') !== false) {
                    // explode segments of given route, Controller@Method
                    $handler = explode('@', $handler);
                }

                $response = $controllerDispatcher->dispatch(
                    $request, // = request object
                    $handler[0], // = controller class
                    $handler[1], // = controller method
                    $vars // =  parameters
                );
            }
        } catch (HttpRouteNotFoundException $e) {
            // throw the exception because we'll handle any exception later on.
            // @see \PytoMVC\System\Exceptions\Handler
            throw $e;
        } catch (HttpMethodNotAllowedException $e) {
            throw $e; // same here.. ^^
        }

        if (! isset($afterFilter)) {
            return $response;
        }

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null)
    {
        while ($filter = array_shift($filters)) {
            if (($filteredResponse = $filter($this->app['request'], $response)) !== null) {
                return $filteredResponse;
            }
        }
        
        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     * @return array
     */
    private function parseFilters($filters)
    {        
        $beforeFilter = [];
        $afterFilter  = [];
        
        if (isset($filters[Route::BEFORE])) {
            $beforeFilter = array_intersect_key(
                $this->filters, array_flip((array)$filters[Route::BEFORE])
            );
        }

        if (isset($filters[Route::AFTER])) {
            $afterFilter = array_intersect_key(
                $this->filters, array_flip((array)$filters[Route::AFTER])
            );
        }
        
        return [$beforeFilter, $afterFilter];
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }
        
        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (! isset($routes[$httpMethod])) {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }
        
        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod)
    {
        $additional = [Route::ANY];
        
        if ($httpMethod === Route::HEAD) {
            $additional[] = Route::GET;
        }
        
        foreach ($additional as $method) {
            if (isset($routes[$method])) {
                return $method;
            }
        }
        
        $this->matchedRoute = $routes;
        
        throw new HttpMethodNotAllowedException('Allowed request methods: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpMethodNotAllowedException
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data)  {
            if (! preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $count = count($matches);

            while (! isset($data['routeMap'][$count++]));
            
            $routes = $data['routeMap'][$count - 1];

            if (! isset($routes[$httpMethod])) {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            } 

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName) {
                if (! isset($matches[$i + 1]) || $matches[$i + 1] === '') {
                    unset($routes[$httpMethod][2][$varName]);
                } else {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}
