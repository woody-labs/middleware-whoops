# Middleware Whoops

**Whoops** is a nice little library that helps you develop and maintain your projects better,
by helping you deal with errors and exceptions in a less painful way.

https://filp.github.io/whoops/

![Whoops](https://filp.github.io/whoops/screen.png)


## Installation

To make this middleware work, you need to include the `filp/whoops` in your project.

````bash
composer require --dev filp/whoops
````


## Implementation

Any exception thrown in deeper middleware will be caught by Whoops to generate a dedicated page.

````php
// @todo: generate request

$debug = true;

// Dispatch request into middleware stack.
$dispatcher = new Dispatcher($debug);
$dispatcher->pipe(new WhoopsMiddleware());
$dispatcher->pipe(new MyAppMiddleware());

// @todo: add other middleware

$response = $dispatcher->handle($request);
````
