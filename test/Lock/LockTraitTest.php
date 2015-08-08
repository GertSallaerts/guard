<?php

use BuboBox\Guard\Guard;
use BuboBox\Guard\Lock\LockInterface;
use BuboBox\Guard\Lock\LockTrait;
use BuboBox\Guard\Resource\GenericResource;
use BuboBox\Guard\Caller\GenericCaller;

class SomeLock implements LockInterface
{
    use LockTrait;
}

class LockTraitTest extends PHPUnit_Framework_TestCase
{
    protected $lock;

    protected $guard;

    public function setUp()
    {
        $this->guard = new Guard();
        $this->lock = new SomeLock();
        $this->lock->setGuard($this->guard);
    }

    /**
     * @expectedException BuboBox\Guard\NoGuardException
     */
    public function testNoGuardException()
    {
        $lock = new SomeLock();
        $lock->allow('foo');
    }

    public function testAllow()
    {
        $this->lock->allow('read');
        $this->lock->allow('write', 'person');
        $this->lock->allow('see', 'person', 'name');

        // We can't delete anything
        $this->assertFalse($this->lock->can('delete'));
        $this->assertFalse($this->lock->can('delete', 'foo'));
        $this->assertFalse($this->lock->can('delete', 'foo', 'bar'));

        // We can read everything
        $this->assertTrue($this->lock->can('read'));
        $this->assertTrue($this->lock->can('read', 'foo'));
        $this->assertTrue($this->lock->can('read', 'foo', 'bar'));

        // We can write to some resource
        $this->assertTrue($this->lock->can('write'));

        // But that resource is only person
        $this->assertTrue($this->lock->can('write', 'person'));
        $this->assertTrue($this->lock->can('write', 'person', 'foo'));
        $this->assertFalse($this->lock->can('write', 'foo'));
        $this->assertFalse($this->lock->can('write', 'foo', 'bar'));

        // We can see, we can see person, but we can only see the name property of a person
        $this->assertTrue($this->lock->can('see'));
        $this->assertTrue($this->lock->can('see', 'person'));
        $this->assertTrue($this->lock->can('see', 'person', 'name'));
        $this->assertFalse($this->lock->can('see', 'person', 'email'));
    }

    public function testDeny()
    {
        $this->lock->allow('read');
        $this->lock->allow('write');
        $this->lock->allow('delete');

        $this->lock->deny('read', 'person', 'name');
        $this->lock->deny('write', 'person');
        $this->lock->deny('delete');

        // We can read, read person, but not person's name
        $this->assertTrue($this->lock->can('read'));
        $this->assertTrue($this->lock->can('read', 'foo'));
        $this->assertTrue($this->lock->can('read', 'foo', 'bar'));
        $this->assertTrue($this->lock->can('read', 'person'));
        $this->assertTrue($this->lock->can('read', 'person', 'bar'));
        $this->assertFalse($this->lock->can('read', 'person', 'name'));

        // We can write, but not to person
        $this->assertTrue($this->lock->can('write'));
        $this->assertTrue($this->lock->can('write', 'foo'));
        $this->assertTrue($this->lock->can('write', 'foo', 'bar'));
        $this->assertFalse($this->lock->can('write', 'person'));
        $this->assertFalse($this->lock->can('write', 'person', 'bar'));

        // We cannot delete
        $this->assertFalse($this->lock->can('delete'));
        $this->assertFalse($this->lock->can('delete', 'foo'));
        $this->assertFalse($this->lock->can('delete', 'foo', 'bar'));
    }

    public function testResourceObject()
    {
        $resource = new GenericResource('foo');

        $this->lock->allow('read', $resource);

        $this->assertTrue($this->lock->can('read', 'foo'));
        $this->assertTrue($this->lock->can('read', $resource));
    }

    public function testAssert()
    {
        $this->lock->allow('read', 'person', 'name', function () { return true; });
        $this->lock->allow('read', 'person', 'email', function () { return false; });

        $this->assertTrue($this->lock->can('read', 'person', 'name'));
        $this->assertFalse($this->lock->can('read', 'person', 'email'));
    }

    public function testAssertParams()
    {
        $test = $this;
        $assertsCalled = 0;

        $this->lock->allow('bar', 'baz', 'barbaz', function ($caller, $action, $resource, $property) use ($test, &$assertsCalled) {
            $test->assertEmpty($caller);
            $test->assertEquals('bar', $action);
            $test->assertEquals('baz', $resource);
            $test->assertEquals('barbaz', $property);
            $assertsCalled++;
        });

        $this->lock->can('bar', 'baz', 'barbaz');

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

        $this->lock->can('bar', 'baz', 'barbaz');

        $this->assertEquals(2, $assertsCalled, 'Both assert methods were called');
    }

    public function testNullParams()
    {
        $this->lock->allow('read', null, null);

        $this->assertTrue($this->lock->can('read'));
        $this->assertTrue($this->lock->can('read', 'foo'));
        $this->assertTrue($this->lock->can('read', 'foo', 'bar'));
    }
}
