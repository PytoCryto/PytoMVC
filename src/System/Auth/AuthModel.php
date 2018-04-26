<?php

namespace PytoMVC\System\Auth;

use Closure;
use PytoMVC\System\Http\Request;
use PytoMVC\System\Database\EloquentModel;

class AuthModel extends EloquentModel
{
    /**
     * @var \PytoMVC\System\Auth\User
     */
    private $currentUserModelResolver;

    /**
     * Form validation rules
     *
     * @var array
     */
    protected $rules =  [
        'username' => 'required',
        'password' => 'required'
    ];

    /**
     * The default column for authentication
     * 
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * The default field for the captcha
     * 
     * @return string
     */
    public function captcha()
    {
        return 'g-recaptcha-response';
    }

    /**
     * Try to authenticate the user
     * 
     * @param  \PytoMVC\System\Http\Request $request 
     * @param  \Closure|null                    $next 
     * @return bool
     */
    public function tryAuthenticate(Request $request, Closure $next = null)
    {
        $this->beforeLogin($request);

        if ($this->captchaEnabled()) {
            $this->registerCaptchaRule();
        }

        $validator = app('validator')->make($data = $request->all(), $this->rules);

        if ($validator->passes()) {
            $user = $this->resolveCurrentUserModel()
                        ->where($this->username(), $data['username'])
                        ->first();

            if ($user && app('hash')->check($data['password'], $user->password)) {
                $session = $request->getSession();

                $session->regenerate();
                $session->updateActivityTime();
                $session->set('logged_in', true)
                        ->set('currentIp', $request->clientIp());

                $this->saveSessionData($request, $user->attributes);

                $this->afterLogin($request, $user);

                return true;
            }
        }

        return false;
    }

    /**
     * Set the current User model resolver callback
     *
     * @param  \Closure  $resolver
     * @return void
     */
    protected function currentUserModelResolver(Closure $resolver)
    {
        $this->currentUserModelResolver = $resolver;
    }

    /**
     * Resolve the current User model and return the instance
     * 
     * @return \PytoMVC\System\Auth\User
     */
    protected function resolveCurrentUserModel()
    {
        if (isset($this->currentUserModelResolver)) {
            return call_user_func($this->currentUserModelResolver);
        }

        return new User;
    }

    /**
     * Indicates whether the captcha validation is required
     * 
     * @return bool
     */
    protected function captchaEnabled()
    {
        return config('captcha.enabled');
    }

    /**
     * Get the desired rule(s) for the captcha field
     * 
     * @return string
     */
    protected function getCaptchaRule()
    {
        return 'required|recaptcha';
    }

    /**
     * Register the validation rules for the captcha field
     * 
     * @return void
     */
    protected function registerCaptchaRule()
    {
        $this->rules[$this->captcha()] = $this->getCaptchaRule();
    }

    protected function saveSessionData($request, $attributes)
    {
        Auth::setUser($attributes);

        return $this;
    }

    protected function beforeLogin($request)
    {
        //
    }

    protected function afterLogin($request, $user)
    {
        //
    }

    public function getSessionData()
    {
        return $this->request->session()->get('user_data');
    }
}
