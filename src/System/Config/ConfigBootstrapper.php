<?php

namespace PytoMVC\System\Config;

use DirectoryIterator;
use PytoMVC\Core\App;
use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class ConfigBootstrapper
{
    public function __construct()
    {
        //
    }

    /**
     * Bootstrap the given application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @return \Illuminate\Config\Repository
     */
    public function bootstrap(App $app)
    {
        $items = [];
        $loadedFromCache = false;

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.

        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        $app->instance('config', $config = new Repository($items));

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.

        if (! $loadedFromCache) {
            $this->loadConfigurationFiles($app, $config);
        }

        if (true === $this->needsNewCache($app, $cached)) {
            $this->loadConfigurationFiles($app, $config);

            $config = $config->all();

            $files = $app['files'];
            $files->delete($cached);
            $files->put(
                $cached, '<?php return ' . var_export($config, true) . ';' . PHP_EOL
            );

            $app->instance(
                'config', $config = new Repository(require($cached))
            );
        }

        return $config;
    }

    /**
     * Check if the configuration has been updated and needs to be re-cached.
     * 
     * @param  \PytoMVC\Core\App $app 
     * @param  string                $cached 
     * @return null|bool
     */
    protected function needsNewCache(App $app, $cached)
    {
        if (! $app->configurationIsCached()) {
            return true;
        }

        $path = realpath($app->configPath());

        // foreach ($this->getConfigurationFiles($app) as $key => $path)
        // so much faster with DirectoryIterator lol,
        // Symfony y u do dis slow with \Symfony\Component\Finder? :(
        foreach (new DirectoryIterator($path) as $path) {
            if ($path->getBasename()[0] === '.' || $path->isDir() || $path->getExtension() !== 'php') {
                continue;
            }

            if ($path->getMTime() > filemtime($cached)) {
                return true; // cached config needs auto update
            }
        }
    }

    /**
     * Load the configuration items from all of the files.
     *
     * @param  \PytoMVC\Core\App  $app
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @return void
     */
    protected function loadConfigurationFiles(App $app, RepositoryContract $repository)
    {
        foreach ($this->getConfigurationFiles($app) as $key => $path) {
            $repository->set($key, require($path));
        }
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  \PytoMVC\Core\App  $app
     * @return array
     */
    protected function getConfigurationFiles(App $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $nesting = $this->getConfigurationNesting($file, $configPath);

            $files[$nesting . basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Get the configuration file nesting path.
     *
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @param  string  $configPath
     * @return string
     */
    protected function getConfigurationNesting(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($tree = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $tree = str_replace(DIRECTORY_SEPARATOR, '.', $tree) . '.';
        }

        return $tree;
    }
}
