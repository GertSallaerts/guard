# Guard

Fine grained permission checking library built with REST resources in mind.

## Install

```
composer require bubobox/guard
```

## Usage

There are two ways to use the library. Either with a `Guard` instance or by turning your own classes (e.g. the class you use for the currently active user) into locks by implementing the `LockInterface` on them.

### Guard

A guard object can be used to setup and check rules on your resources.

A `Guard` option can be created by calling its constructor with the `new` keyword or using the class' static `make`method. Both approaches take one optional parameter. This optional parameter is the caller (generic name for user, apikey, ...) associated with this `Guard` instance. Callers must implement the `CallerInterface` interface. At this point in time, the caller is only used to pass to your [assertion methods](#assertions).

- `allow($action = '*', $resource = '*', $property = '*', callable $assertion = null)`
- `deny($action = '*', $resource = '*', $property = '*')`
- `can($action, $resource = null, $property = null)`

Allows cascade down, so calling `allow('read')` will tell the Guard that the `read` action is allowed on every resource and every property of those resources. The

Denies are only useful when you have already allowed something. Say you call `allow('read')` and afterwards `deny('read', 'foo')`. Now Guard will allow the `read` action on every resource except `foo`. They also cascade down, when you deny access to an action, it will effect all resources. Denies always supersede allows, once you deny something it does not matter if you allow it again afterwards.

You can add your own custom check by adding an assertion callable to the `allow` call. See [assertions](#assertions) for more information.

`can` will return true or false, based on the rules defined in the guard.

### Lock

By adding the `LockTrait` to any class, it will be fully compliant with the `LockInterface`, you could write your own implmentation instead of using `LockTrait` but this should be pointless in most cases. By implementing `LockInterface` on your classes, they will expose the same three methods as the `Guard` explained above as well as one extra `setGuard` function. The `setGuard` function needs to be called once to supply your `LockInterface` instance with a `Guard` to use in the background, if you do not do this, a `NoGuardException` will be raised.

## Complex resources

Instead of using a string for the resource, your resource can also be an instance of a class that implements the `ResourceInterface` interface.

## Assertions

An assertion is a method that is called to add extra checks to the guard. It can be any `callable` and will be passed these parameters in order: `caller` (CallerInterface of null), `action` (string), `resource` (ResourceInterface, string or null) and `property` (string or null). Caller will be the CallerInterface associated with the `Guard` instance, while action, resource and property will be the values that were originally passed to the `can` method.
