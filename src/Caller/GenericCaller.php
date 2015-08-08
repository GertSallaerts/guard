<?php

namespace BuboBox\Guard\Caller;

use BuboBox\Guard\Caller\CallerInterface;

class GenericCaller implements CallerInterface
{
    protected $caller_id;

    public function __cosntruct($caller_id)
    {
        $this->caller_id = $caller_id;
    }

    public function getCallerId()
    {
        return $this->caller_id;
    }
}
