<?php

namespace PytoMVC\System\Database\Sluggable;

/**
 * This package belongs to Spatie\Sluggable
 * 
 * @see https://github.com/spatie/laravel-sluggable/
 * @license https://github.com/spatie/laravel-sluggable/blob/master/LICENSE.md
 */
class SlugOptions
{
    /** @var string|array|callable */
    public $generateSlugFrom;

    /** @var string */
    public $slugField;

    /** @var bool */
    public $generateUniqueSlugs = true;

    /** @var int */
    public $maximumLength = 250;

    /** @var bool */
    public $generateSlugsOnCreate = true;

    /** @var bool */
    public $generateSlugsOnUpdate = true;

    /** @var string */
    public $slugSeparator = '-';

    public static function create()
    {
        return new static();
    }

    /**
     * @param string|array|callable $fieldName
     *
     * @return \PytoMVC\System\Database\Sluggable\SlugOptions
     */
    public function generateSlugsFrom($fieldName)
    {
        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo($fieldName)
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs()
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan($maximumLength)
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function doNotGenerateSlugsOnCreate()
    {
        $this->generateSlugsOnCreate = false;

        return $this;
    }

    public function doNotGenerateSlugsOnUpdate()
    {
        $this->generateSlugsOnUpdate = false;

        return $this;
    }

    public function usingSeparator($separator)
    {
        $this->slugSeparator = $separator;

        return $this;
    }
}
