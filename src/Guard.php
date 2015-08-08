<?php

namespace BuboBox\Guard;

use BuboBox\Guard\Caller\CallerInterface;
use BuboBox\Guard\Resource\ResourceInterface;

class Guard
{
    protected $caller;

    protected $allowed = [];

    protected $denied = [];

    protected $assertions = [];

    public static function make(CallerInterface $caller = null)
    {
        return new static($caller);
    }

    public function __construct(CallerInterface $caller = null)
    {
        $this->caller = $caller;
    }

    public function allow($action = '*', $resource = '*', $property = '*', callable $assertion = null)
    {
        $action = $this->emptyToStar($action);
        $resource = $this->emptyToStar($this->getName($resource));
        $property = $this->emptyToStar($property);

        $this->allowed[$action] = isset($this->allowed[$action]) ? $this->allowed[$action] : [];

        $this->allowed[$action][$resource] =
            isset($this->allowed[$action][$resource]) ?
            $this->allowed[$action][$resource] :
            []
        ;

        $this->allowed[$action][$resource][$property] = true;

        if ($assertion) {
            $this->assertions[$action] = isset($this->assertions[$action]) ? $this->assertions[$action] : [];

            $this->assertions[$action][$resource] =
                isset($this->assertions[$action][$resource]) ?
                $this->assertions[$action][$resource] :
                []
            ;

            $this->assertions[$action][$resource][$property] =
                isset($this->assertions[$action][$resource][$property]) ?
                $this->assertions[$action][$resource][$property] :
                []
            ;

            $this->assertions[$action][$resource][$property][] = $assertion;
        }

        return $this;
    }

    public function deny($action = '*', $resource = '*', $property = '*')
    {
        $action = $this->emptyToStar($action);
        $resource = $this->emptyToStar($this->getName($resource));
        $property = $this->emptyToStar($property);

        $deny = $action !== '*' ? $action : '';
        $deny .= $action !== '*' && $resource !== '*' ? '.' . $resource : '';
        $deny .= $action !== '*' && $resource !== '*' && $property !== '*' ? '.' . $property : '';

        if (!empty($deny)) {
            $this->denied[] = $deny;
        }

        return $this;
    }

    public function can($action, $resource = null, $property = null)
    {
        $resourceName = $this->getName($resource);

        $checks = [ $action ];
        if ($resourceName) {
            $checks[] = $resourceName;
        }
        if ($resourceName && $property) {
            $checks[] = $property;
        }
        $count = count($checks);

        $holder = $this->allowed;
        $deniedCheck = '';

        for ($i=0; $i < $count; $i++) {
            $check = $checks[$i];

            $deniedCheck = trim($deniedCheck . '.' . $check, '.');
            $denied = in_array($deniedCheck, $this->denied);

            if ($denied) {
                return false;
            }

            $allowed = isset($holder[$check]) || isset($holder['*']);

            if (!$allowed) {
                return false;
            }

            $holder = !isset($holder[$check]) ? !isset($holder['*']) ? [] : $holder['*'] : $holder[$check];
        }

        $assertions = $this->getAssertions(array_pad($checks, 3, '*'));

        foreach ($assertions as $assert) {
            if (!$this->checkAssert($action, $resource, $property, $assert)) {
                return false;
            }
        }

        return true;
    }

    protected function getName($thing)
    {
        if ($thing instanceof ResourceInterface) {
            return $thing->getResourceId();
        }

        if ($thing instanceof CallerInterface) {
            return $thing->getCallerId();
        }

        return $thing;
    }

    protected function emptyToStar($value)
    {
        return empty($value) ? '*' : $value;
    }

    protected function getAssertions($checks)
    {
        $assertions = [];

        if (isset($this->assertions[$checks[0]]) &&
            isset($this->assertions[$checks[0]]['*']) &&
            isset($this->assertions[$checks[0]]['*']['*'])
        ) {
            $assertions = $this->assertions[$checks[0]]['*']['*'];
        }

        if (isset($this->assertions[$checks[0]]) &&
            isset($this->assertions[$checks[0]][$checks[1]]) &&
            isset($this->assertions[$checks[0]][$checks[1]]['*'])
        ) {
            $assertions = array_merge($assertions, $this->assertions[$checks[0]][$checks[1]]['*']);
        }

        if (isset($this->assertions[$checks[0]]) &&
            isset($this->assertions[$checks[0]][$checks[1]]) &&
            isset($this->assertions[$checks[0]][$checks[1]][$checks[2]])
        ) {
            $assertions = array_merge($assertions, $this->assertions[$checks[0]][$checks[1]][$checks[2]]);
        }


        return $assertions;
    }

    protected function checkAssert($action, $resource, $property, $assert)
    {
        return call_user_func($assert, $this->caller, $action, $resource, $property);
    }
}
