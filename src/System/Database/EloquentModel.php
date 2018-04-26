<?php

namespace PytoMVC\System\Database;

use PytoMVC\System\Http\Request;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentModel extends Model
{
    /**
     * @var type
     */
    private $originalRules;

    /**
     * Holds the current request instance
     * 
     * @var \PytoMVC\System\Http\Request
     */
    protected $request;

    /**
     * Create a new Eloquent model instance
     *
     * @param  array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->request = app('request');
    }

    /**
     * @override
     */
    public function updateData($attributes = [], array $options = [])
    {
        return parent::update((array)$attributes, $options);
    }

    /**
     * Save the model attributes to the database
     *
     * @param  array $options
     * @return bool
     */
    public function saveData($data, $options = [])
    {
        // @todo: MAKE THIS DEPRECATED :(
        if (is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $key => $value) {
            parent::setAttribute($key, $value);
        }

        return parent::save($options);
    }
}
