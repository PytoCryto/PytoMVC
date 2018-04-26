<?php

namespace PytoMVC\System\Routing;

use Closure;
use PytoMVC\Core\App;
use PytoMVC\System\Support\Str;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Http\Response;
use PytoMVC\System\Files\Filesystem;
use PytoMVC\System\Routing\Phroute\Dispatcher;
use PytoMVC\System\Routing\Phroute\RouteCollector;

class RouteManager
{
    /**
     * @var \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    private $routeCollection;

    /**
     * @var \PytoMVC\Core\App
     */
    protected $app;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \PytoMVC\System\Files\Filesystem
     */
    protected $files;

    /**
     * Create an instance
     * 
     * @param  \PytoMVC\Core\App                   $app 
     * @param  \PytoMVC\System\Files\Filesystem    $files 
     * @return void
     */
    public function __construct(App $app, Filesystem $files)
    {
        $this->app = $app;
        $this->files = $files;
        $this->config = $app['config'];
        $this->routeCollection = new RouteCollector;
    }

    /**
     * Get the dispatcher
     * 
     * @param  \Closure $next 
     * @return \Closure
     */
    public function getDispatcher()
    {
        return new Dispatcher(
            $this->getRouteData($this->app->getRoutesList()), $this->app
        );
    }

    /**
     * Get the route collector instance
     * 
     * @return \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    public function getRouteCollection()
    {
        return $this->routeCollection;
    }

    /**
     * Get the routes
     * 
     * @param  array $group 
     * @return \PytoMVC\System\Routing\Phroute\RouteDataArray
     */
    protected function getRouteData($group)
    {
        $app = $this->app;
        $basePath = $app->routePath();

        // load our filters/middleware
        $this->collectFrom([$basePath . '/filters.php']);

        if (! $this->config->get('cache.router.enabled')) {
            return $this->collectFrom($group)->getData();
        }

        $cacheGroup = [];
        $needsRefresh = ! $app->routesAreCached();

        $this->prepareCachingGroup(
            $basePath, $cachePath = $app->getCachedRoutesPath(), $needsRefresh, $cacheGroup
        );

        if ($needsRefresh) {
            $this->collectFrom($cacheGroup);

            $this->cacheRoutes($cachePath);
        }

        $routeDataArray = $this->readFromCache($cachePath);

        if (count($nonCachedRoutes = array_diff($group, $cacheGroup)) > 0) {
            // collect routes from a group without caching it
            $routeDataArray->mergeRouteDataArray(
                $this->collectFrom($nonCachedRoutes)->getData()
            );
        }

        return $routeDataArray;
    }

    /**
     * Prepare the group of files for caching if necessary
     * 
     * @param  string $basePath 
     * @param  string $cachePath 
     * @param  bool   &$needsRefresh 
     * @param  array  &$cacheGroup 
     * @return void
     */
    protected function prepareCachingGroup($basePath, $cachePath, &$needsRefresh, &$cacheGroup)
    {
        foreach ((array) $this->config->get('cache.router.routes') as $file) {
            $path = $basePath . '/' . ltrim($file, '/');
            $cacheGroup[] = $path;

            if (! file_exists($cachePath) || filemtime($path) > filemtime($cachePath)) {
                $needsRefresh = true;
            }
        }
    }

    /**
     * Collect routes from a group of files
     * 
     * @param  array $group 
     * @return \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    protected function collectFrom(array $group)
    {
        $app = $this->app;
        $router = $this->getRouteCollection();

        if (count($group) == 0) {
            return $router;
        }

        foreach ($group as $file) {
            if (basename($file) == 'api.php' || Str::startsWith(basename($file), 'api_')) {
                $router->group('/api', function ($router) use ($app, $file) {
                    require $file;
                });
            } else {
                require $file;
            }
        }

        return $router;
    }

    /**
     * Cache the routes
     * 
     * @param  string $path 
     * @return void
     */
    protected function cacheRoutes($path)
    {
        $data = $this->getRouteCollection()->getData();

        $this->files->put(
            $path, base64_encode(serialize($data))
        );
    }

    /**
     * Read the routes from the cache
     * 
     * @param  string $path 
     * @return \PytoMVC\System\Routing\Phroute\RouteDataArray
     */
    protected function readFromCache($path)
    {
        return unserialize(base64_decode($this->files->get($path)));
    }
}
