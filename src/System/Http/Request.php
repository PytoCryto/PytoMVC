<?php

namespace PytoMVC\System\Http;

use PytoMVC\System\Auth\Auth;
use Illuminate\Http\Request as LaravelRequest;

class Request extends LaravelRequest
{
    /**
     * Description
     * 
     * @return string
     */
    public function getPreviousRoute()
    {
        return $this->server->get('HTTP_REFERER');
    }

    /**
     * Description
     * 
     * @return string
     */
    public function getPrevious()
    {
        return $this->headers->get('referer');
    }

    /**
     * Description
     * 
     * @param  string $queryKey 
     * @return string
     */
    public function searchQuery($queryKey = 'query')
    {
        return trim($this->query->get($queryKey));
    }

    /**
     * Description
     * 
     * @param  string $queryKey 
     * @return int
     */
    public function getPaginatorPage($queryKey = 'page')
    {
        $page = (int)$this->query->get($queryKey, 1);

        if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
            return $page;
        }
    }

    /**
     * Description
     * 
     * @return type
     */
    public function getSession()
    {
        return app('session');
    }

    /**
     * Description
     * 
     * @return type
     */
    public function getFlash()
    {
        return $this->getSession()->getFlash();
    }

    /**
     * Description
     * 
     * @param  string $filter 
     * @param  array  $keys 
     * @return type
     */
    public function flash($filter = null, $keys = [])
    {
        return $this->getFlash();
    }

    /**
     * Description
     * 
     * @return type
     */
    public function session()
    {
        return $this->getSession();
    }

    /**
     * Description
     * 
     * @return type
     */
    public function getUser()
    {
        return Auth::user();
    }

    /**
     * Description
     * 
     * @param  string|null $guard 
     * @return type
     */
    public function user($guard = null)
    {
        return $this->getUser();
    }

    /**
     * Description
     * 
     * @param  string $key 
     * @return type
     */
    public function hasPost($key)
    {
        return $this->request->has($key);
    }

    /**
     * Description
     * 
     * @param  string $key 
     * @return type
     */
    public function hasGet($key)
    {
        return $this->query->has($key);
    }

    /**
     * Description
     * 
     * @return type
     */
    public function getRoute()
    {
        return $this->getPathInfo();
    }

    /**
     * Description
     * 
     * @return type
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Description
     * 
     * @return type
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Returns the client IP address
     *
     * @return string
     */
    public function clientIp()
    {
        return $this->getClientIp();
    }

    /**
     * Set the JSON payload for the request
     *
     * @param  array $json
     * @return $this
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }
}
