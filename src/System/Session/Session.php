<?php

namespace PytoMVC\System\Session;

use Closure;
use Illuminate\Support\Arr;

class Session implements SessionInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var bool
     */
    private $hasExpired = false;

    /**
     * @var array
     */
    protected $defaults = [
        'lifetime'       => '20 minutes',
        'path'           => '/',
        'domain'         => null,
        'secure'         => false,
        'httponly'       => false,
        'name'           => 'pytomvc_session',
        'autorefresh'    => false,
        'gc_probability' => 1,
        'gc_divisor'     => 1,
    ];

    /**
     * @var type
     */
    protected $flash;

    /**
     * @var bool
     */
    protected $sessionStarted = false;

    /**
     * @var array
     */
    protected $settings;

    public function __construct($settings = [])
    {
        $settings = array_merge($this->defaults, $settings);

        if (is_string($lifetime = $settings['lifetime'])) {
            $settings['lifetime'] = strtotime($lifetime) - time();
        }

        $this->settings = $settings;

        static::$instance = $this;

        ini_set('session.gc_probability', $settings['gc_probability']);
        ini_set('session.gc_divisor', $settings['gc_divisor']);
        ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
    }

    public function start()
    {
        if ($this->sessionStarted === true) {
            return;
        }

        $settings = $this->settings;
        $inactive = session_status() === PHP_SESSION_NONE;

        $name = $settings['name'];

        session_set_cookie_params(
            $settings['lifetime'],
            $settings['path'],
            $settings['domain'],
            $settings['secure'],
            $settings['httponly']
        );

        if ($inactive) {
            // Refresh session cookie when "inactive",
            // else PHP won't know we want this to refresh
            if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
                setcookie(
                    $name,
                    $_COOKIE[$name],
                    time() + $settings['lifetime'],
                    $settings['path'],
                    $settings['domain'],
                    $settings['secure'],
                    $settings['httponly']
                );
            }
        }

        session_name($name);
        session_cache_limiter(false);

        if ($inactive) {
            $this->sessionStarted = true;

            session_start();
        }
    }

    /**
     * Get the Session instance
     *
     * @return Session
     */
    public static function getInstance($settings = [])
    {
        if (! isset(static::$instance)) {
            static::$instance = new static($settings);
        }

        return static::$instance;
    }

    public function setName($name)
    {
        $this->settings['name'] = $name;
    }

    /**
     * Get a session variable.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->exists($key)
            ? $_SESSION[$key]
            : $default;
    }

    /**
     * Set a session variable.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * Merge values recursively.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function merge($key, $value)
    {
        if (is_array($value) && is_array($old = $this->get($key))) {
            $value = array_merge_recursive($old, $value);
        }

        return $this->set($key, $value);
    }

    /**
     * Delete a session variable.
     *
     * @param string $key
     *
     * @return $this
     */
    public function delete($key)
    {
        if ($this->exists($key)) {
            $_SESSION[$key] = null;
            unset($_SESSION[$key]);
        }

        return $this;
    }

    /**
     * Alias
     */
    public function erase($key)
    {
        return $this->delete($key);
    }

    /**
     * Clear all session variables.
     *
     * @return $this
     */
    public function clear()
    {
        $_SESSION = [];

        return $this;
    }

    /**
     * Check if a session variable is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Alias for exists()
     */
    public function has($key)
    {
        return $this->exists($key);
    }

    /**
     * Generate the current session ID.
     * 
     * @return $this
     */
    public function regenerate()
    {
        $this->id(true);

        return $this;
    }

    /**
     * Get or regenerate current session ID.
     *
     * @param bool $new
     *
     * @return string
     */
    public function id($new = false)
    {
        if ($new && session_id()) {
            @session_regenerate_id(true);
        }

        return session_id() ?: '';
    }

    /**
     * Destroy the session.
     */
    public function destroy()
    {
        if ($this->id()) {
            $this->clear();

            session_unset();
            session_destroy();
            session_write_close();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 4200,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }
    }

    /**
     * Description
     * 
     * @param  int $time 
     * @return $this
     */
    public function expire($time = 1)
    {
        if ($this->has('expire')) {
            $expireTime = $time * 60;

            if ((time() - $this->get('expire')) >= $expireTime) {
                $this->hasExpired = true;

                $this->erase('expire');
            }
        }

        return $this;
    }

    /**
     * Description
     * 
     * @param  \Closure $callback 
     * @return null|mixed
     */
    public function whenInactive(Closure $callback)
    {
        if ($this->hasExpired) {
            return $callback($this);
        }

        return null;
    }

    public function updateActivityTime($time = null)
    {
        $this->set('expire', is_null($time) ? time() : $time);

        return $this;
    }

    public function token($name = null)
    {
        return app('csrf')->getToken($name);
    }

    public function setFlash($flash)
    {
        $this->flash = $flash;
    }

    public function getFlash()
    {
        return $this->flash;
    }

    /**
     * Magic method for get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic method for set.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method for delete.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->delete($key);
    }

    /**
     * Magic method for exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        return count($_SESSION);
    }

    /**
     * Retrieve an external Iterator.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($_SESSION);
    }

    /**
     * Whether an array offset exists.
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Retrieve value by offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set a value by offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Remove a value by offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * 
     * -- Laravel session stuff --
     * 
    */

    /**
     * Age the flash data for the session.
     *
     * @return void
     */
    public function ageFlashData()
    {
        $this->forget($this->get('_flash.old', []));

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    /**
     * Remove one or many items from the session.
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys)
    {
        Arr::forget($_SESSION, $keys);
    }

    /**
     * Flash a key / value pair to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function flash($key, $value)
    {
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Flash a key / value pair to the session for immediate use.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function now($key, $value)
    {
        $this->put($key, $value);

        $this->push('_flash.old', $key);
    }

    /**
     * Flash an input array to the session.
     *
     * @param  array  $value
     * @return void
     */
    public function flashInput(array $value)
    {
        $this->flash('_old_input', $value);
    }

    /**
     * Reflash all of the session flash data.
     *
     * @return void
     */
    public function reflash()
    {
        $this->mergeNewFlashes($this->get('_flash.old', []));

        $this->put('_flash.old', []);
    }

    /**
     * Reflash a subset of the current flash data.
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function keep($keys = null)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $this->mergeNewFlashes($keys);

        $this->removeFromOldFlashData($keys);
    }

    /**
     * Merge new flash keys into the new flash array.
     *
     * @param  array  $keys
     * @return void
     */
    protected function mergeNewFlashes(array $keys)
    {
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));

        $this->put('_flash.new', $values);
    }

    /**
     * Remove the given keys from the old flash data.
     *
     * @param  array  $keys
     * @return void
     */
    protected function removeFromOldFlashData(array $keys)
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Push a value onto a session array.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);
        $array[] = $value;

        $this->put($key, $array);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param  string|array  $key
     * @param  mixed       $value
     * @return void
     */
    public function put($key, $value = null)
    {
        if (! is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            $this->set($arrayKey, $arrayValue);
        }
    }

    /**
     * Determine if the session contains old input.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasOldInput($key = null)
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    /**
     * Get the requested item from the flashed input array.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        $input = $this->get('_old_input', []);
        // Input that is flashed to the session can be easily retrieved by the
        // developer, making repopulating old forms and the like much more
        // convenient, since the request's previous input is available.
        return Arr::get($input, $key, $default);
    }
}
