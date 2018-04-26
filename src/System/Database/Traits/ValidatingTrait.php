<?php

namespace PytoMVC\System\Database\Traits;

/**
 * @mixin \Eloquent
 * @source: https://github.com/dwightwatson/validating/issues/179#issuecomment-311640817
 */
trait ValidatingTrait
{
    use \Watson\Validating\ValidatingTrait;

    /**
     * @param  array  $attributes
     * @return static
     */
    public static function newOrFail(array $attributes = [])
    {
        $model = new static($attributes);

        $model->isValidOrFail();

        return $model;
    }

    /**
     * @param  array  $attributes
     * @param  array  $options
     * @return static
     */
    public static function createOrFail(array $attributes = [], array $options = [])
    {
        $model = static::newOrFail($attributes);

        $model->save($options);

        return $model;
    }

    /**
     * @param  array  $attributes
     * @return static
     */
    public static function firstOrNewOrFail(array $attributes = [])
    {
        $model = static::firstOrNew($attributes);

        if (! $model->exists) {
            $model->isValidOrFail();
        }

        return $model;
    }

    /**
     * @param  array  $attributes
     * @param  array  $options
     * @return static
     */
    public static function firstOrCreateOrFail(array $attributes = [], array $options = [])
    {
        $model = static::firstOrNewOrFail($attributes);

        $model->save($options);

        return $model;
    }

    /**
     * @param  array  $attributes
     * @param  array  $options
     * @return $this
     */
    public function updateOrFail(array $attributes = [], array $options = [])
    {
        $this->fill($attributes)->saveOrFail($options);

        return $this;
    }
}
