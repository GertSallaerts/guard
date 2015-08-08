<?php

namespace BuboBox\Guard\Resource;

use BuboBox\Guard\Resource\ResourceInterface;

class GenericResource implements ResourceInterface
{
    protected $resource_id;

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }

    public function getResourceId()
    {
        return $this->resource_id;
    }
}
