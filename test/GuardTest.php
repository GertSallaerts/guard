<?php

use BuboBox\Guard\Guard;
use BuboBox\Guard\Resource\GenericResource;
use BuboBox\Guard\Caller\GenericCaller;

class GuardTest extends PHPUnit_Framework_TestCase
{
    protected $guard;

    public function setUp()
    {
        $this->guard = new Guard();
    }

    public function testAllow()
    {
        $this->guard->allow('read');
        $this->guard->allow('write', 'person');
        $this->guard->allow('see', 'person', 'name');

        // We can't delete anything
        $this->assertFalse($this->guard->can('delete'));
        $this->assertFalse($this->guard->can('delete', 'foo'));
        $this->assertFalse($this->guard->can('delete', 'foo', 'bar'));

        // We can read everything
        $this->assertTrue($this->guard->can('read'));
        $this->assertTrue($this->guard->can('read', 'foo'));
        $this->assertTrue($this->guard->can('read', 'foo', 'bar'));

        // We can write to some resource
        $this->assertTrue($this->guard->can('write'));

        // But that resource is only person
        $this->assertTrue($this->guard->can('write', 'person'));
        $this->assertTrue($this->guard->can('write', 'person', 'foo'));
        $this->assertFalse($this->guard->can('write', 'foo'));
        $this->assertFalse($this->guard->can('write', 'foo', 'bar'));

        // We can see, we can see person, but we can only see the name property of a person
        $this->assertTrue($this->guard->can('see'));
        $this->assertTrue($this->guard->can('see', 'person'));
        $this->assertTrue($this->guard->can('see', 'person', 'name'));
        $this->assertFalse($this->guard->can('see', 'person', 'email'));
    }

    public function testDeny()
    {
        $this->guard->allow('read');
        $this->guard->allow('write');
        $this->guard->allow('delete');

        $this->guard->deny('read', 'person', 'name');
        $this->guard->deny('write', 'person');
        $this->guard->deny('delete');

        // We can read, read person, but not person's name
        $this->assertTrue($this->guard->can('read'));
        $this->assertTrue($this->guard->can('read', 'foo'));
        $this->assertTrue($this->guard->can('read', 'foo', 'bar'));
        $this->assertTrue($this->guard->can('read', 'person'));
        $this->assertTrue($this->guard->can('read', 'person', 'bar'));
        $this->assertFalse($this->guard->can('read', 'person', 'name'));

        // We can write, but not to person
        $this->assertTrue($this->guard->can('write'));
        $this->assertTrue($this->guard->can('write', 'foo'));
        $this->assertTrue($this->guard->can('write', 'foo', 'bar'));
        $this->assertFalse($this->guard->can('write', 'person'));
        $this->assertFalse($this->guard->can('write', 'person', 'bar'));

        // We cannot delete
        $this->assertFalse($this->guard->can('delete'));
        $this->assertFalse($this->guard->can('delete', 'foo'));
        $this->assertFalse($this->guard->can('delete', 'foo', 'bar'));
    }

    public function testResourceObject()
    {
        $resource = new GenericResource('foo');

        $this->guard->allow('read', $resource);

        $this->assertTrue($this->guard->can('read', 'foo'));
        $this->assertTrue($this->guard->can('read', $resource));
    }

    public function testAssert()
    {
        $this->guard->allow('read', 'person', 'name', function () { return true; });
        $this->guard->allow('read', 'person', 'email', function () { return false; });

        $this->assertTrue($this->guard->can('read', 'person', 'name'));
        $this->assertFalse($this->guard->can('read', 'person', 'email'));
    }

    public function testAssertParams()
    {
        $test = $this;
        $assertsCalled = 0;

        $this->guard->allow('bar', 'baz', 'barbaz', function ($caller, $action, $resource, $property) use ($test, &$assertsCalled) {
            $test->assertEmpty($caller);
            $test->assertEquals('bar', $action);
            $test->assertEquals('baz', $resource);
            $test->assertEquals('barbaz', $property);
            $assertsCalled++;
        });

        $this->guard->can('bar', 'baz', 'barbaz');

        $fixt_caller = new GenericCaller('foo');
        $fixt_resource = new GenericResource('baz');
        $guardWithCaller = Guard::make($fixt_caller);

        $guardWithCaller->allow('bar', $fixt_resource, 'barbaz', function ($caller, $action, $resource, $property) use ($test, &$assertsCalled) {
            $test->assertEquals($fixt_caller, $caller);
            $test->assertEquals('bar', $action);
            $test->assertEquals($fixt_resource, $resource);
            $test->assertEquals('barbaz', $property);
            $assertsCalled++;
        });

        $this->guard->can('bar', 'baz', 'barbaz');

        $this->assertEquals(2, $assertsCalled, 'Both assert methods were called');
    }

    public function testNullParams()
    {
        $this->guard->allow('read', null, null);

        $this->assertTrue($this->guard->can('read'));
        $this->assertTrue($this->guard->can('read', 'foo'));
        $this->assertTrue($this->guard->can('read', 'foo', 'bar'));
    }
}
