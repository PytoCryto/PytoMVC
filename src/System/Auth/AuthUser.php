<?php

namespace PytoMVC\System\Auth;

use BadMethodCallException;
use PytoMVC\System\Session\SessionInterface;

class AuthUser
{
    /**
     * @var \PytoMVC\System\Session\SessionInterface
     */
    private $session;

    /**
     * @var \PytoMVC\System\Auth\GenericUser
     */
    private $user;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Determine if the user is authenticated
     * 
     * @return bool
     */
    private function authenticated()
    {
        return $this->session->get('logged_in') === true && ! is_null($this->user());
    }

    /**
     * Reset the user property
     * 
     * @return void
     */
    protected function resetUser()
    {
        $this->user = null;
    }

    /**
     * Logout the current user
     * 
     * @return void
     */
    public function logout()
    {
        $session = $this->session->regenerate();

        $session->delete('logged_in');

        $session->delete('user');

        $this->resetUser();
    }

    /**
     * Check if the user is authenticated
     * 
     * @return bool
     */
    public function check()
    {
        return $this->authenticated();
    }

    /**
     * Set the user attributes
     * 
     * @param  array $attributes 
     * @return void
     */
    public function setUser(array $attributes)
    {
        $this->session->set(
            'user', $this->user = new GenericUser($attributes)
        );
    }

    /**
     * Retrieve the current userdata
     * 
     * @return mixed
     */
    public function getUser()
    {
        return $this->session->get('user');
    }

    /**
     * Alias for getUser()
     */
    public function user()
    {
        return $this->getUser();
    }

    /**
     * @deprecated
     */
    public function loggedOut()
    {
        throw new BadMethodCallException("Method (" . __METHOD__ . ") is deprecated. Use Auth::check() instead.");
    }

    /**
     * @deprecated
     */
    public function loggedin()
    {
        throw new BadMethodCallException("Method (" . __METHOD__ . ") is deprecated. Use Auth::check() instead.");
    }
}
