<?php

namespace BuboBox\Guard\Lock;

use BuboBox\Guard\Guard;

interface LockInterface
{
    public function setGuard(Guard $guard);

    public function allow($action, $resource, $property, $assertion);

    public function deny($action, $resource, $property);

    public function can($action, $resource, $property);
}
