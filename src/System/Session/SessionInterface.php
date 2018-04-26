<?php

namespace PytoMVC\System\Session;

interface SessionInterface
{
    /**
     * Get a session value
     * 
     * @param  string   $key
     * @param  string   $default    Returned if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Set a session value
     * 
     * @param string    $key
     * @param mixed     $value
     */
    public function set($key, $value);

    /**
     * Check if a session key exists
     * 
     * @param  string   $key
     * @return boolean
     */
    public function has($key);

    /**
     * Delete a session key/value
     * 
     * @param  string   $key
     */
    public function delete($key);

    /**
     * Set a session flash value
     * 
     * @param string    $key
     * @param mixed     $value
     */
    public function destroy();
}
