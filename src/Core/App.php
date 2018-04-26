<?php

namespace PytoMVC\Core;

use Closure;
use Throwable;
use Exception;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use PytoMVC\System\Exceptions\HandleExceptions;
use PytoMVC\System\Auth\Auth;
use PytoMVC\System\Config\ConfigBootstrapper;
use PytoMVC\System\Files\AliasLoader;
use PytoMVC\System\Files\Filesystem;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Http\Response;
use PytoMVC\System\Security\Csrf;
use PytoMVC\System\Session\Session;
use PytoMVC\System\Session\SessionServiceProvider;
use PytoMVC\System\Support\Str;
use PytoMVC\System\Support\ProviderRepository;
use PytoMVC\System\Log\LoggingBootstrapper;
use PytoMVC\System\View\ViewServiceProvider;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\Macroable;

/**
 * Parts of this class has been taken from the Laravel /foundation/Application and some from the Lumen, thanks Taylor :D
 * 
 * @see https://github.com/laravel/framework/blob/5.3/src/Illuminate/Foundation/Application.php
 */
class App extends Container
{
    use Macroable;

    /**
     * The framework version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * The class alias loader instance
     * 
     * @var \PytoMVC\System\Files\AliasLoader
     */
    protected $aliasLoader;

    /**
     * The current request instance
     * 
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;

    /**
     * The current response instance
     * 
     * @var \PytoMVC\System\Http\Response
     */
    protected $response;

    /**
     * The configuration instance
     * 
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The core application instance
     * 
     * @var \PytoMVC\Core\App
     */
    public static $app;

    /**
     * The base path for the application installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of files to load routes from
     * 
     * @var array
     */
    protected $routesList = [];

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = [];

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = [];

    /**
     * A custom callback used to configure Monolog.
     *
     * @var callable|null
     */
    protected $monologConfigurator;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The application start time.
     * 
     * @var mixed
     */
    protected $startTime;

    /**
     * The default container aliases
     * 
     * @var array
     */
    protected $defaultContainerAliases = [
        'app'      => ['PytoMVC\Core\App', 'Illuminate\Contracts\Container\Container'],
        'request'  => ['PytoMVC\System\Http\Request'],
        'response' => ['PytoMVC\System\Http\Response'],
        'session'  => ['PytoMVC\System\Session\Session'],
        'files'    => ['PytoMVC\System\Files\Filesystem'],
        'config'   => ['PytoMVC\System\Config\Config', 'Illuminate\Config\Repository', 'Illuminate\Contracts\Config\Repository'],
    ];

    /**
     * The default service providers
     * 
     * @var array
     */
    protected $defaultServiceProviders = [
        'PytoMVC\System\Cache\CacheServiceProvider',
        'PytoMVC\System\Hashing\HashServiceProvider',
        'PytoMVC\System\Validation\ValidationServiceProvider',
        'PytoMVC\System\Http\Forms\FormRequestServiceProvider',
        'Illuminate\Translation\TranslationServiceProvider',
    ];

    /**
     * Create a new application instance
     *
     * @param  string|null $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        static::$app = $this;

        $this->startTime = microtime(true);

        $this->bootstrapContainer();

        $this->registerCoreContainerAliases();

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->ensurePathsExist();

        $this->registerBindings();

        $this->bootstrapConfig();

        $this->registerBaseServiceProviders();
    }

    /**
     * Get the application start timestamp
     * 
     * @return mixed
     */
    protected function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Get the version number of the application
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Description
     * 
     * @return void
     */
    protected function ensurePathsExist()
    {
        $storage = $this->storagePath();
        $paths = [
            $this->bootstrapPath() . '/cache/',
            $storage . '/app/public/',
            $storage . '/framework/cache/',
            $storage . '/framework/views/',
            $storage . '/logs/',
        ];

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    /**
     * Bootstrap the application container
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance('PytoMVC\Core\App', $this);

        $this->instance('Illuminate\Container\Container', $this);
    }

    /**
     * Register all of the base service providers
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new ViewServiceProvider($this));

        $this->register(new SessionServiceProvider($this));
    }

    /**
     * Register the necessary objects
     * 
     * @return void
     */
    protected function boot()
    {
        if ($this->booted) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->fireAppCallbacks($this->bootedCallbacks);

        $this->booted = true;

        $this->createSession();

        (new HandleExceptions)->bootstrap($this);

        (new LoggingBootstrapper)->bootstrap($this);
    }

    /**
     * Determine if the request is an API request
     * 
     * @param  \PytoMVC\System\Http\Request|null $request 
     * @return bool
     */
    public function isRequestViaApi(Request $request = null)
    {
        $request = $request ?: $this['request'];

        return $request->is('api/*'); // $request->segment(1) == 'api'
    }

    /**
     * Description
     * 
     * @return void
     */
    protected function createSession()
    {
        $session = $this->make('session');
        $session->start();

        $flash = \PytoCryto\Flash\FlashMessage::getInstance();
        $flash->config($this->config['session.flash']);

        $session->setFlash($flash);
    }

    /**
     * Description
     * 
     * @return void
     */
    protected function registerBindings()
    {
        $this->singleton('csrf', function () {
            return new Csrf();
        });

        $this->singleton('auth', function ($app) {
            return new Auth($app['session']);
        });

        $this->singleton('request', function () {
            return Request::capture();
        });

        $this->singleton('response', function () {
            return new Response();
        });

        // @todo: remove the below
        $this->make('auth');
        $this->request = $this->make('request');
    }

    /**
     * Call the routers mapping methods (GET, POST, PUT etc..) dynamically
     * 
     * @param  string $method 
     * @param  array  $args 
     * @return \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    public function __call($method, $args)
    {
        return $this->getRouteCollection()->$method(...$args);
    }

    /**
     * Run the application and send the response
     * 
     * @param  \Closure                              $next 
     * @param  \PytoMVC\System\Http\Request|null $request
     * @return \PytoMVC\System\Http\Response
     */
    public function run(Closure $next, $request = null)
    {
        try {
            $this->boot();

            $request = $request ?: $this['request'];

            $response = $this['router']->getDispatcher()->dispatch($request);
        } catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e));

            $response = $this->renderException($request, $e);
        }

        $request->getSession()->ageFlashData();

        return $next($response);
    }

    /**
     * Report the exception to the exception handler.
     *
     * @param  \Exception $e
     * @return void
     */
    protected function reportException(Exception $e)
    {
        $this[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the exception to a response.
     *
     * @param  \PytoMVC\System\Http\Request $request
     * @param  \Exception                       $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderException($request, Exception $e)
    {
        return $this[ExceptionHandler::class]->render($request, $e);
    }

    /**
     * Get the route collection instance
     * 
     * @return \PytoMVC\System\Routing\Phroute\RouteCollector
     */
    public function getRouteCollection()
    {
        return $this['router']->getRouteCollection();
    }

    /**
     * Add a group of routes
     * 
     * @param  array|string $routes 
     * @return void
     */
    public function group($routes)
    {
        $this->routesList = array_merge($this->routesList, (array) $routes);
    }

    /**
     * Get the group of routes
     * 
     * @return type
     */
    public function getRoutesList()
    {
        return $this->routesList;
    }

    /**
     * Load the configuration
     * 
     * @return $this
     */
    public function bootstrapConfig()
    {
        $this->singleton('files', function () {
            return new Filesystem();
        });

        $this->singleton('config', function ($app) {
            return (new ConfigBootstrapper)->bootstrap($app);
        });

        $this->config = $this->make('config');

        $this->withTimezone()->withHttpProxies();

        return $this;
    }

    /**
     * Register all facades & aliases
     * 
     * @return void
     */
    public function withFacades()
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($this);

        AliasLoader::getInstance($this->config->get('app.aliases', []))->register();
    }

    /**
     * Set the default timezone (if any configured)
     * 
     * @return $this
     */
    protected function withTimezone()
    {
        $timezone = $this->config->get('app.timezone', 'UTC');

        if (! empty($timezone)) {
            date_default_timezone_set($timezone);
        }

        return $this;
    }

    /**
     * Description
     * 
     * @return void
     */
    protected function withHttpProxies()
    {
        if ($this->config->get('app.proxy.enabled')) {
            $trustedProxies = $this->config->get('app.proxy.proxy_ip');

            if (! is_array($trustedProxies)) {
                $trustedProxies = [$trustedProxies];
            }

            if (empty($trustedProxies)) { // No proxy ip set, trust all proxies.. (be careful)
                $trustedProxies = ['127.0.0.1', $this['request']->server->get('REMOTE_ADDR')];
            }

            $this['request']->setTrustedProxies($trustedProxies);
        }
    }

    /**
     * Enable the Eloquent ORM
     * 
     * @return $this
     */
    public function withEloquent()
    {
        $this->singleton('db', function ($app) {
            return new \Illuminate\Database\Capsule\Manager($app);
        });

        $capsule = $this->make('db');

        $capsule->addConnection($this->config->get('database'));

        $capsule->setAsGlobal();

        $capsule->bootEloquent();

        return $this;
    }

    /**
     * Enable pagination for Eloquent models
     * 
     * @return $this
     */
    public function withPaginator()
    {
        \Illuminate\Pagination\Paginator::viewFactoryResolver(function () {
            return $this['view'];
        });

        \Illuminate\Pagination\Paginator::currentPathResolver(function () {
            return $this['request']->url();
        });

        \Illuminate\Pagination\Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $this['request']->input($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return $page;
            }

            return 1;
        });

        return $this;
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        $aliases = array_merge($this->defaultContainerAliases, [
            'auth'        => ['PytoMVC\System\Auth\Auth'],
            'cache'       => ['Illuminate\Cache\CacheManager', 'Illuminate\Contracts\Cache\Factory'],
            'cache.store' => ['Illuminate\Cache\Repository', 'Illuminate\Contracts\Cache\Repository'],
            'db'          => ['Illuminate\Database\Capsule\Manager'],
            'events'      => ['Illuminate\Events\Dispatcher', 'Illuminate\Contracts\Events\Dispatcher'],
            'translator'  => ['Illuminate\Translation\Translator', 'Symfony\Component\Translation\TranslatorInterface'],
            'session'     => ['PytoMVC\System\Session\Session', 'PytoMVC\System\Session\SessionInterface'],
            'log'         => ['Illuminate\Log\Writer', 'Illuminate\Contracts\Logging\Log', 'Psr\Log\LoggerInterface'],
            'validator'   => ['Illuminate\Validation\Factory', 'Illuminate\Contracts\Validation\Factory'],
            'hash'        => ['PytoMVC\System\Hashing\Hasher'],
            'view'        => ['PytoTPL\PytoTPL'],
            'url'         => ['PytoMVC\System\Routing\UrlGenerator'],
            'router'      => ['PytoMVC\System\Routing\RouteManager'],
        ]);

        foreach ($aliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush()
    {
        parent::flush();

        $this->loadedProviders = [];
    }

    /**
     * Set the base path for the application.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.lang', $this->langPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    /**
     * Get the base path of the application installation.
     *
     * @return string
     */
    public function basePath()
    {
        return $this->basePath;
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @return string
     */
    public function bootstrapPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap';
    }

    /**
     * Get the path to the routes directory.
     * 
     * @return string
     */
    public function routePath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'routes';
    }

    /**
     * Get the path to the application configuration files.
     *
     * @return string
     */
    public function configPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config';
    }

    /**
     * Get the path to the database directory.
     *
     * @return string
     */
    public function databasePath()
    {
        return $this->databasePath ?: $this->basePath . DIRECTORY_SEPARATOR . 'database';
    }

    /**
     * Set the database directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function langPath()
    {
        return $this->resourcePath() . DIRECTORY_SEPARATOR . 'lang';
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'public';
    }

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath()
    {
        return $this->storagePath ?: $this->basePath . DIRECTORY_SEPARATOR . 'storage';
    }

    /**
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * Get the path to the resources directory.
     *
     * @return string
     */
    public function resourcePath()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'resources';
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerProviders()
    {
        $providers = array_merge($this->defaultServiceProviders, $this->config->get('app.providers'));

        $manifestPath = $this->getCachedServicesPath();

        (new ProviderRepository($this, $this['files'], $manifestPath))
                    ->load($providers);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by this developer's application logic.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::first($this->serviceProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this['events']->fire($class = get_class($provider), [$provider]);

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string  $service
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if (! isset($this->deferredServices[$service])) {
            return;
        }

        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if (! isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service) {
            unset($this->deferredServices[$service]);
        }

        $this->register($instance = new $provider($this));

        if (! $this->booted) {
            $this->booting(function () use ($instance) {
                $this->bootProvider($instance);
            });
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * (Overriding Container::bound)
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->deferredServices[$abstract]) || parent::bound($abstract);
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  array  $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * Determine if the application configuration is cached.
     *
     * @return bool
     */
    public function configurationIsCached()
    {
        return file_exists($this->getCachedConfigPath());
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this->bootstrapPath() . '/cache/config.php';
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached()
    {
        return $this['files']->exists($this->getCachedRoutesPath());
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return $this->bootstrapPath() . '/cache/routes.php';
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return $this->bootstrapPath() . '/cache/compiled.php';
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->bootstrapPath() . '/cache/services.php';
    }

    /**
     * Throw an HttpException with the given data.
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        if ($code == 404) {
            throw new NotFoundHttpException($message);
        }

        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Get the application's deferred services.
     *
     * @return array
     */
    public function getDeferredServices()
    {
        return $this->deferredServices;
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Add an array of services to the application's deferred services.
     *
     * @param  array  $services
     * @return void
     */
    public function addDeferredServices(array $services)
    {
        $this->deferredServices = array_merge($this->deferredServices, $services);
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string  $service
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Define a callback to be used to configure Monolog.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function configureMonologUsing(callable $callback)
    {
        $this->monologConfigurator = $callback;

        return $this;
    }

    /**
     * Determine if the application has a custom Monolog configurator.
     *
     * @return bool
     */
    public function hasMonologConfigurator()
    {
        return ! is_null($this->monologConfigurator);
    }

    /**
     * Get the custom Monolog configurator for the application.
     *
     * @return callable
     */
    public function getMonologConfigurator()
    {
        return $this->monologConfigurator;
    }

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->config->get('app.locale');
    }

    /**
     * Set the current application locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->config->set('app.locale', $locale);

        $this['translator']->setLocale($locale);

        $this['events']->fire('locale.changed', [$locale]);
    }

    /**
     * Determine if application locale is the given locale.
     *
     * @param  string  $locale
     * @return bool
     */
    public function isLocale($locale)
    {
        return $this->getLocale() == $locale;
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath(app_path()) == realpath(base_path().'/'.$pathChoice)) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }
}
