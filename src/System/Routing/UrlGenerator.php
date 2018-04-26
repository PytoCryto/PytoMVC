<?php

namespace PytoMVC\System\Routing;

use InvalidArgumentException;
use PytoMVC\System\Support\Str;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Routing\Phroute\RouteCollector;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Arr;

/**
 * This file belongs to Laravel and was edited to my needs.
 * @source: https://github.com/laravel/framework/blob/5.3/src/Illuminate/Routing/UrlGenerator.php
 */
class UrlGenerator
{
    use Macroable;

    /**
     * The route collection.
     *
     * @var \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    protected $routes;

    /**
     * The request instance.
     *
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;

    /**
     * The forced URL root.
     *
     * @var string
     */
    protected $forcedRoot;

    /**
     * The forced schema for URLs.
     *
     * @var string
     */
    protected $forceSchema;

    /**
     * A cached copy of the URL root for the current request.
     *
     * @var string|null
     */
    protected $cachedRoot;

    /**
     * A cached copy of the URL schema for the current request.
     *
     * @var string|null
     */
    protected $cachedSchema;

    /**
     * The root namespace being applied to controller actions.
     *
     * @var string
     */
    protected $rootNamespace;

    /**
     * The session resolver callable.
     *
     * @var callable
     */
    protected $sessionResolver;

    /**
     * Characters that should not be URL encoded.
     *
     * @var array
     */
    protected $dontEncode = [
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%3B' => ';',
        '%2C' => ',',
        '%3D' => '=',
        '%2B' => '+',
        '%21' => '!',
        '%2A' => '*',
        '%7C' => '|',
        '%3F' => '?',
        '%26' => '&',
        '%23' => '#',
        '%25' => '%',
    ];

    /**
     * Create a new URL Generator instance.
     *
     * @param  \PytoMVC\System\Http\Request                   $request
     * @param  \PytoMVC\System\Routing\Phroute\RouteCollector $collection
     * @return void
     */
    public function __construct(Request $request, RouteCollector $collection)
    {
        $this->routes = $collection;
        $this->setRequest($request);
    }

    /**
     * Get the full URL for the current request.
     *
     * @return string
     */
    public function full()
    {
        return $this->request->fullUrl();
    }

    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current()
    {
        return $this->to($this->request->getPathInfo());
    }

    /**
     * Get the URL for the previous request.
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false)
    {
        $referrer = $this->request->headers->get('referer');

        $url = $referrer ? $this->to($referrer) : false;

        if (! $referrer && ! $fallback) {
            $url = config('app.url');
        }

        if ($url) {
            return $url;
        } elseif ($fallback) {
            return $this->to($fallback);
        } else {
            return $this->to('/');
        }
    }

    /**
     * Generate an absolute URL to the given path.
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $scheme = $this->getScheme($secure);

        $extra = $this->formatParameters($extra);

        $tail = implode('/', array_map(
            'rawurlencode', (array) $extra)
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $this->getRootUrl($scheme);

        if (($queryPosition = strpos($path, '?')) !== false) {
            $query = substr($path, $queryPosition);
            $path = substr($path, 0, $queryPosition);
        } else {
            $query = '';
        }

        return $this->trimUrl($root, $path, $tail).$query;
    }

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * @param  string  $path
     * @param  array   $parameters
     * @return string
     */
    public function secure($path, $parameters = [])
    {
        return $this->to($path, $parameters, true);
    }

    /**
     * Generate the URL to an application asset.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset($path, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->getRootUrl($this->getScheme($secure));

        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Generate the URL to an asset from a custom root domain such as CDN, etc.
     *
     * @param  string  $root
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function assetFrom($root, $path, $secure = null)
    {
        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        $root = $this->getRootUrl($this->getScheme($secure), $root);

        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Remove the index.php file from a path.
     *
     * @param  string  $root
     * @return string
     */
    protected function removeIndex($root)
    {
        $i = 'index.php';

        return Str::contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
    }

    /**
     * Generate the URL to a secure asset.
     *
     * @param  string  $path
     * @return string
     */
    public function secureAsset($path)
    {
        return $this->asset($path, true);
    }

    /**
     * Get the scheme for a raw URL.
     *
     * @param  bool|null  $secure
     * @return string
     */
    protected function getScheme($secure)
    {
        if (is_null($secure)) {
            if (is_null($this->cachedSchema)) {
                $this->cachedSchema = $this->forceSchema ?: $this->request->getScheme().'://';
            }

            return $this->cachedSchema;
        }

        return $secure ? 'https://' : 'http://';
    }

    /**
     * Force the schema for URLs.
     *
     * @param  string  $schema
     * @return void
     */
    public function forceSchema($schema)
    {
        $this->cachedSchema = null;

        $this->forceSchema = $schema.'://';
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        if (! is_null($route = $this->routes->route($name, $parameters))) {
            return $route;
        }

        throw new InvalidArgumentException("Route [{$name}] not defined.");
    }


    /**
     * Replace all of the wildcard parameters for a route path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRouteParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters);

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            return (empty($parameters) && ! Str::endsWith($match[0], '?}'))
                        ? $match[0]
                        : array_shift($parameters);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceNamedParameters($path, &$parameters)
    {
        return preg_replace_callback('/\{(.*?)\??\}/', function ($m) use (&$parameters) {
            return isset($parameters[$m[1]]) ? Arr::pull($parameters, $m[1]) : $m[0];
        }, $path);
    }

    /**
     * Add a query string to the URI.
     *
     * @param  string  $uri
     * @param  array  $parameters
     * @return mixed|string
     */
    protected function addQueryString($uri, array $parameters)
    {
        // If the URI has a fragment, we will move it to the end of this URI since it will
        // need to come after any query string that may be added to the URL else it is
        // not going to be available. We will remove it then append it back on here.
        if (! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
            $uri = preg_replace('/#.*/', '', $uri);
        }

        $uri .= $this->getRouteQueryString($parameters);

        return is_null($fragment) ? $uri : $uri."#{$fragment}";
    }

    /**
     * Format the array of URL parameters.
     *
     * @param  mixed|array  $parameters
     * @return array
     */
    protected function formatParameters($parameters)
    {
        return $this->replaceRoutableParameters($parameters);
    }

    /**
     * Replace UrlRoutable parameters with their route parameter.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceRoutableParameters($parameters = [])
    {
        $parameters = is_array($parameters) ? $parameters : [$parameters];

        return $parameters;
    }

    /**
     * Get the query string for a given route.
     *
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteQueryString(array $parameters)
    {
        // First we will get all of the string parameters that are remaining after we
        // have replaced the route wildcards. We'll then build a query string from
        // these string parameters then use it as a starting point for the rest.
        if (count($parameters) == 0) {
            return '';
        }

        $query = http_build_query(
            $keyed = $this->getStringParameters($parameters)
        );

        // Lastly, if there are still parameters remaining, we will fetch the numeric
        // parameters that are in the array and add them to the query string or we
        // will make the initial query string if it wasn't started with strings.
        if (count($keyed) < count($parameters)) {
            $query .= '&'.implode(
                '&', $this->getNumericParameters($parameters)
            );
        }

        return '?'.trim($query, '&');
    }

    /**
     * Get the string parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getStringParameters(array $parameters)
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the numeric parameters from a given list.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getNumericParameters(array $parameters)
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Add the port to the domain if necessary.
     *
     * @param  string  $domain
     * @return string
     */
    protected function addPortToDomain($domain)
    {
        $secure = $this->request->isSecure();

        $port = (int) $this->request->getPort();

        if (($secure && $port === 443) || (! $secure && $port === 80)) {
            return $domain;
        }

        return $domain.':'.$port;
    }

    /**
     * Get the base URL for the request.
     *
     * @param  string  $scheme
     * @param  string  $root
     * @return string
     */
    protected function getRootUrl($scheme, $root = null)
    {
        if (is_null($root)) {
            if (is_null($this->cachedRoot)) {
                $this->cachedRoot = $this->forcedRoot ?: $this->request->root();
            }

            $root = $this->cachedRoot;
        }

        $start = Str::startsWith($root, 'http://') ? 'http://' : 'https://';

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }

    /**
     * Set the forced root URL.
     *
     * @param  string  $root
     * @return void
     */
    public function forceRootUrl($root)
    {
        $this->forcedRoot = rtrim($root, '/');
        $this->cachedRoot = null;
    }

    /**
     * Determine if the given path is a valid URL.
     *
     * @param  string  $path
     * @return bool
     */
    public function isValidUrl($path)
    {
        if (Str::startsWith($path, ['#', '//', 'mailto:', 'tel:', 'http://', 'https://'])) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Format the given URL segments into a single URL.
     *
     * @param  string  $root
     * @param  string  $path
     * @param  string  $tail
     * @return string
     */
    protected function trimUrl($root, $path, $tail = '')
    {
        return trim($root.'/'.trim($path.'/'.$tail, '/'), '/');
    }

    /**
     * Get the request instance.
     *
     * @return \PytoMVC\System\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
     *
     * @param  \PytoMVC\System\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        $this->cachedRoot = null;
        $this->cachedSchema = null;
    }

    /**
     * Get the session implementation from the resolver.
     *
     * @return \Illuminate\Session\Store|null
     */
    protected function getSession()
    {
        if ($this->sessionResolver) {
            return call_user_func($this->sessionResolver);
        }
    }

    /**
     * Set the session resolver for the generator.
     *
     * @param  callable  $sessionResolver
     * @return $this
     */
    public function setSessionResolver(callable $sessionResolver)
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    /**
     * Set the root controller namespace.
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}