<?php

namespace PytoMVC\Core;

use BadMethodCallException;
use Illuminate\Container\Container;

class Model
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function load($model)
    {
        throw new BadMethodCallException('This is yet to be completed.');
    }
}
