<?php

namespace PytoMVC\System\Validation;

use PytoMVC\System\Helpers\RainCaptcha;
use PytoMVC\System\Helpers\ReCaptcha;
use Illuminate\Validation\Factory;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\ValidationServiceProvider as LaravelValidationProvider;

class ValidationServiceProvider extends LaravelValidationProvider
{
    /**
     * Register our custom validation rules.
     * 
     * @param  \Illuminate\Validation\Factory $validator 
     * @return \Illuminate\Validation\Factory
     */
    protected function registerCustomRules(Factory $validator)
    {
        $validator->extend('captcha', function ($attribute, $value) {
            // register captcha validation rule (if not already obvious..)
            // @todo: remove this since RainCaptcha v1 doesn't exist anymore
            return (new RainCaptcha)->checkAnswer($value);
        });

        $validator->extend('recaptcha', function ($attribute, $value) {
            // register google recaptcha v2 validation rule (if not already obvious too..)
            $app = $this->app;

            return (new ReCaptcha(
                $app['request'],
                $app['config']['captcha.secret'],
                $app['config']['captcha.sitekey']
            ))->verify($value);
        });

        $validator->extendImplicit('as', function ($attribute, $value, $parameters, $validator) {
            // This rule allows us to use pretty input aliases,
            // or as defined in the $customAttributes parameter
            // via $rules = ['my_ugly_input_name' => 'as:NiceNameHere|required'];
            // Make sure its the first rule you define!

            $validator->addCustomAttributes([
                $attribute => $parameters[0]
            ]);

            return 1;
        });

        return $validator;
    }

    /**
     * Register the validation factory.
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence
            // of values in a given data collection, typically a relational database or
            // other persistent data stores. And it is used to check for uniqueness.
            if (isset($app['db']) && isset($app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $this->registerCustomRules($validator);
        });
    }

    /**
     * Register the database presence verifier.
     *
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']->getDatabaseManager());
        });
    }
}
