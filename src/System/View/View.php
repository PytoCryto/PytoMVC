<?php

namespace PytoMVC\System\View;

use PytoCryto\PytoCryto;
use Illuminate\Contracts\Config\Repository;

final class View extends PytoCryto
{
    /**
     * The config repository
     * 
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $repository;

    public function __construct(Repository $config)
    {
        parent::__construct($config['template']);

        $this->repository = $config;
    }

    public function make($view, $data = [])
    {
        // this is only for the laravel paginator, to get it working properly
        if (strpos($view, 'pagination::') !== false) {
            $view = str_replace('pagination::', 'vendor.pagination.', $view);
        } else {
            die('Yo, watcha doin? This is only for the Paginator.');
        }

        return new PaginatorView($this->render($view, $data));
    }
}
