<?php

namespace PytoMVC\System\Hashing;

use Illuminate\Support\Facades\Facade;

/**
 * @see \PytoMVC\System\Hashing\Hasher
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hash';
    }
}
