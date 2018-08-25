<?php declare(strict_types = 1);

namespace MangowebTests\Tester\PresenterTester;

use Mangoweb\Tester\Infrastructure\TestCase;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Tester\Assert;

$factory = require __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class PresenterTesterListenerTest extends TestCase
{
	public function testRender(PresenterTester $presenterTester, TestPresenterTesterListener $listener)
	{
		$listener->enabled = true;
		$request = $presenterTester->createRequest('Example');
		// action is added in listener
		$response = $presenterTester->execute($request);

		Assert::noError(function () use ($response) {
			$response->assertRenders(['Hello world']);
		});
		Assert::same($response, $listener->passedResult);
	}
}


PresenterTesterListenerTest::run($factory);
