<?php

namespace PytoMVC\System\Traits;

use Closure;
use BadMethodCallException;

trait MacroableTrait
{
    /**
     * The registered string macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param  string   $name
     * @param  callable $macro
     * @return void
     */
    public static function macro($name, callable $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if macro is registered.
     *
     * @param  string $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return array_key_exists($name, static::$macros);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if(static::hasMacro($method))
        {
            if(static::$macros[$method] instanceof Closure)
                return call_user_func_array(Closure::bind(static::$macros[$method], null, get_called_class()), $parameters);
            else
                return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if(static::hasMacro($method))
        {
            if(static::$macros[$method] instanceof Closure)
                return call_user_func_array(static::$macros[$method]->bindTo($this, get_class($this)), $parameters);
            else
                return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}