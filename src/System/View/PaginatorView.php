<?php

namespace PytoMVC\System\View;

class PaginatorView
{
    /**
     * @var string
     */
    private $content;

    /**
     * Description
     * 
     * @param  string $content 
     * @return void
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Description
     * 
     * @return string
     */
    public function render()
    {
        return $this->content;
    }
}
