<?php

namespace PytoMVC\System\Http;

use PytoMVC\System\Http\Request;
use PytoMVC\System\Session\SessionInterface;
use Illuminate\Support\MessageBag;
use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Contracts\Support\MessageProvider;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RedirectResponse extends LaravelRedirectResponse
{
    public function __construct($url, $status = 302, $headers = [])
    {
        parent::__construct($url, $status, $headers);

        $this->setCustomSession(app('session'));
    }

    /**
     * The request instance.
     *
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;
    
    /**
     * The session store implementation.
     *
     * @var \PytoMVC\System\Session\Session
     */
    protected $session;

    /**
     * Flash a container of errors to the session.
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array|string  $provider
     * @param  string  $key
     * @return $this
     */
    public function withErrors($provider, $key = 'default')
    {
        $value = $this->parseErrors($provider);

        foreach ($value->all() as $message) {
            $this->withError($message);
        }

        return $this;
    }

    /**
     * Parse the given errors into an appropriate value.
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array|string  $provider
     * @return \Illuminate\Support\MessageBag
     */
    protected function parseErrors($provider)
    {
        if ($provider instanceof MessageProvider) {
            return $provider->getMessageBag();
        }

        return new MessageBag((array) $provider);
    }

    /**
     * Flash a piece of data to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return \PytoMVC\System\Http\RedirectResponse
     */
    public function with($key, $value = null)
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->session->getFlash()->$k($v);
        }
        
        return $this;
    }

    public function withError(...$args)
    {
        $this->session->getFlash()->error(...$args);

        return $this;
    }

    public function withWarning(...$args)
    {
        $this->session->getFlash()->warning(...$args);

        return $this;
    }

    public function withSuccess(...$args)
    {
        $this->session->getFlash()->success(...$args);

        return $this;
    }

    public function withInfo(...$args)
    {
        $this->session->getFlash()->info(...$args);

        return $this;
    }

    public function session()
    {
        return $this->getSession();
    }

    /**
     * Get the session store implementation.
     *
     * @return \PytoMVC\System\Session\SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the custom session store implementation.
     *
     * @param  \PytoMVC\System\Session\SessionInterface $session
     * @return void
     */
    public function setCustomSession(SessionInterface $session)
    {
        $this->session = $session;
    }
}
