Mango Presenter Tester
======
[![Build Status](https://travis-ci.org/mangoweb-backend/presenter-tester.svg?branch=master)](https://travis-ci.org/mangoweb-backend/presenter-tester)

Testing tool for Nette presenter with easy to use API.

Installation
----

The recommended way to install is via Composer:

```
composer require mangoweb/presenter-tester
```

It requires PHP version 7.1.

Integration & configuration
-----

If you are using power of Nette DI Container in your tests, you can use Presenter Tester in your current testing environment. All you need is to register PresenterTester service in `.neon` configuration for tests.

```neon
services:
	- Mangoweb\Tester\PresenterTester\PresenterTester(baseUrl: "http://my-app.dev")
```

You can also specify list of [listeners](#listeners):

```neon
services:
	- Mangoweb\Tester\PresenterTester\PresenterTester(
		baseUrl: "http://my-app.dev"
		listeners: [
			MyListener()
		]
	)
```

Other way is to use Presenter Tester together with [mangoweb/tester-infrastructure](https://github.com/mangoweb-backend/tester-infrastructure). In that case you have to register DI extension in infrastructure `.neon` file:

```
extensions:
	mango.presenterTester: Mangoweb\Tester\PresenterTester\Bridges\Infrastructure\PresenterTesterExtension
```

In configuration of the extension you can set a base url and custom [identity factory](#identity-factory).

```
mango.presenterTester:
	baseUrl: http://my-app.dev
	identityFactory: MyIdentityFactory()
```

Usage
----

In Mango Tester Infrastructure environment, service `PresenterTester` is available in infrastructure container. When you get the service, you can start testing your presenters:

```php
$testRequest = $presenterTester->createRequest('Admin:Article')
	->withParameters([
		'action' => 'edit',
		'id' => 1,
	]);
$testResult = $presenterTester->execute($testRequet);
$testResult->assertRenders('%A%Hello world article editation%A%');	
```

As you can see, you first create a `TestPresenterRequest` using `createRequest` method on `PresenterTester`. You pass a presenter name (without an action) and later you configure the test request. You can set additional request parameters like `action` or your own application parameters. There are many other things you can configure on the request, like form values or headers.

After the test request is configured, you pass it to `execute` method, which performs presenter execution and returns `TestPresenterResult`, which wraps `Nette\Application\IResponse` with some additional data collected during execution.

The `TestPresenterResult` contains many useful assert functions like render check or form validity check. In our example there is `assertRenders` method, which asserts that presenter returns `TextResponse` and that the text contains given pattern. You probably already know the pattern format from [Tester\Assert::match()](https://tester.nette.org/en/writing-tests#toc-assert-match) function.

TestPresenterRequest API
-----

**Beware that ``TestPresenterRequest`` is immutable object.**

### `withParameters(array $parameters)`
Set application request parameters.

### `withForm(string $formName, array $post, array $files)`
Add form submission data to request. You have to specify full component tree path to in `$formName`. 

Presenter Tester supports forms with CSRF protection, but since it uses session, it is recommended to install [mangoweb/tester-http-mocks](https://github.com/mangoweb-backend/tester-http-mocks) package.

### `withSignal(string $signal, array $componentParameters = [], string $componentClass = null)`
With Presenter Tester, you can also easily test signal method. The componentClass is only required in the case you are using `nextras/secured-links` (which you should). It is also recommended to install [mangoweb/tester-http-mocks](https://github.com/mangoweb-backend/tester-http-mocks) package.

### `withAjax`
(Not only) signals often uses AJAX, which you can enable using this method.

### `withMethod(string $methodName)`
Change the HTTP method. The default is `GET`. You don't have to explicitly set a method for forms.

### `withHeaders(array $headers)`
Pass additional HTTP headers.

### `withIdentity(Nette\Security\IIdentity $identity)`
Change identity of User, which is executing given request. This is useful when login is required to perform the action. You can implement [identity factory](#identity-factory), which provides a default identity for each request.

### `withPost(array $post)`
### `withFiles(array $files)`
### `withRawBody(string$rawBody)`

TestPresenterResult API
------
It is a result of test execution. It wraps `Nette\Application\IResponse` and adds few methods to check the response easily.

### `assertRenders($match)`
Checks that response is `TextResponse`. Also, you can provide a `$match` parameter to check that response contains some text. You can either pass [pattern](https://tester.nette.org/en/writing-tests#toc-assert-match) or an array plain strings.

### `assertNotRenders($matches)`
Checks that given pattern or strings were not rendered.

### `assertJson($expected)`
Checks that response is JSON. You can optionally pass expected payload.

### `assertBadRequest($code)`
Checks that requests terminates with bad request exception (e.g. 404 not found).

### `assertRedirects(string $presenterName, array $parameters)`
Checks that request redirects to given presenter. You can also pass parameters to check. Extra parameters in redirect request are ignored.

### `assertRedirectsUrl($url)`
### `assertFormValid($formName)`
### `assertFormHasErrors($formName, $formErrors)`
 
-----
Also, there are methods like `getResponse` or `getPresenter` to access original data a perform some custom checks.



Listeners
----

You can hook to some events by implementing `Mangoweb\Tester\PresenterTester\IPresenterTesterListener` interface. Then you can e.g. modify test request or execute some implicit result checks.

To register a listener, simply register it as a service in DI container (infrastructure container if you are using Mango Tester Infrastructure).

Identity factory
----

Using identity factory you can implement a factory which creates a default identity. The factory is a simple PHP callback, which accepts `PresenterTestRequest` and returns `Nette\Security\IIdentity`.
