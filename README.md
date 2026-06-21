# Constructor Overloading for PHP

A small PHP trait that emulates constructor overloading by dispatching to different constructor handlers based on the runtime types of the arguments you pass into `__construct()`.

## What problem it solves

PHP supports only one `__construct()` method. When a class needs multiple initialization paths, the constructor often turns into a long chain of `if`, `instanceof`, and argument-count checks.

This trait lets you keep those paths separate and readable:

- one constructor entry point
- one handler method per argument signature
- no large conditional block inside `__construct()`

It is especially useful in composition-based designs, where one object wraps or coordinates other objects and needs several ways to be built.

## How it works

Inside your constructor, call:

```php
self::overload(func_get_args());
```

The trait inspects the passed arguments and looks for a method named using this pattern:

```text
construct<Type1><Type2><Type3>
```

Examples:

- `constructString`
- `constructStringInt`
- `constructIFormRequestString`
- `constructIFormRequestSArrayISpamRobotAPI`

If the exact type sequence is not found immediately, the trait also tries to refine arrays and objects to more specific names.

## Type naming rules

The repository maps argument types to method-name fragments using these conventions:

- `string` → `String`
- `int` → `Int`
- `float` → `Double`
- `bool` → `Bool`
- `array` → `Array`
- non-empty `int[]` → `IArray`
- non-empty `float[]` → `DArray`
- non-empty `string[]` → `SArray`
- non-empty `bool[]` → `BArray`
- non-empty `object[]` → `OArray`
- object arguments are matched by interface name or class name

## Basic usage

```php
final class User
{
    use ConstructorOverloading;

    private string $name;
    private int $age;

    public function __construct(...$args)
    {
        self::overload($args);
    }

    protected function constructString(string $name): void
    {
        $this->name = $name;
        $this->age = 0;
    }

    protected function constructStringInt(string $name, int $age): void
    {
        $this->name = $name;
        $this->age = $age;
    }
}
```

Usage:

```php
$user1 = new User('John');
$user2 = new User('John', 30);
```

## Composition example

This library is a good fit for decorators and other composition-heavy objects.

Imagine a wrapper around an existing request object. The wrapper can be created in different ways:

- with only the wrapped object
- with the wrapped object and one field name
- with the wrapped object, multiple field names, and an external service

```php
final class SpamProtectedRequest implements IFormRequest
{
    use ConstructorOverloading;

    private IFormRequest $request;
    private array $fieldNames = [];
    private ISpamRobotAPI $api;

    public function __construct(IFormRequest $request)
    {
        $this->request = $request;
        $this->api = new NullSpamRobotAPI();

        self::overload(func_get_args());
    }

    protected function constructIFormRequestString(IFormRequest $request, string $field): void
    {
        $this->request = $request;
        $this->fieldNames = [$field];
    }

    protected function constructIFormRequestSArrayISpamRobotAPI(
        IFormRequest $request,
        array $fields,
        ISpamRobotAPI $api
    ): void {
        $this->request = $request;
        $this->fieldNames = $fields;
        $this->api = $api;
    }
}
```

Client code stays simple:

```php
$request = new SpamProtectedRequest($request);
$request = new SpamProtectedRequest($request, 'token');
$request = new SpamProtectedRequest($request, ['token', 'email'], $robotApi);
```

## When this approach is useful

Use it when:

- the object has several valid initialization shapes
- you are building decorators, adapters, or wrappers
- you want to keep constructor logic split into named methods
- you prefer runtime dispatch based on argument types

## When to avoid it

Do not use it when:

- there is only one obvious constructor shape
- explicit factory methods would be clearer
- you rely heavily on static analysis or IDE constructor hints
- you want the API surface to stay simple and obvious

## Recommended project structure

If you use this in your own application, keep the public constructor minimal and move each initialization path into a dedicated protected method.

That makes the class easier to read and keeps the overloading rules isolated in one place.

## Notes

- The trait expects you to call `self::overload(...)` from `__construct()`.
- Method selection is based on runtime argument types.
- The name matching is convention-based, so method naming must be exact.
- This is a lightweight utility, not a full dependency injection or factory framework.
