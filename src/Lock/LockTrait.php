<?php

namespace Bubobox\Guard\Lock;

use BuboBox\Guard\Guard;
use BuboBox\Guard\NoGuardException;

trait LockTrait
{
    protected $guard_instance = null;

    public function setGuard(Guard $guard)
    {
        $this->guard_instance = $guard;

        return $this;
    }

    protected function getGuard()
    {
        if (!$this->guard_instance) {
            throw new NoGuardException('The Guard instance was not set on Lock of class ' . __CLASS__);
        }

        return $this->guard_instance;
    }

    public function allow($action = '*', $resource = '*', $property = '*', $assertion = null)
    {
        $this->getGuard()->allow($action, $resource, $property, $assertion);
        return $this;
    }

    public function deny($action = '*', $resource = '*', $property = '*')
    {
        $this->getGuard()->deny($action, $resource, $property);
        return $this;
    }

    public function can($action, $resource = null, $property = null)
    {
        return $this->getGuard()->can($action, $resource, $property);
    }
}
