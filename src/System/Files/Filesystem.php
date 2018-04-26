<?php

namespace PytoMVC\System\Files;

use Illuminate\Filesystem\Filesystem as LaravelFS;

class Filesystem extends LaravelFS
{
    /**
     * Load an array of files
     * 
     * @param  array $files 
     * @param  bool  $once
     * @return void
     */
    public function load(array $files, $once = true)
    {
        foreach ($files as $file) {
            if ($once) {
                $this->requireOnce($file);
            } else {
                $this->getRequire($file);
            }
        }
    }
}
