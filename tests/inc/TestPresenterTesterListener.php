<?php declare(strict_types = 1);

namespace MangowebTests\Tester\PresenterTester;

use Mangoweb\Tester\PresenterTester\IPresenterTesterListener;
use Mangoweb\Tester\PresenterTester\TestPresenterRequest;
use Mangoweb\Tester\PresenterTester\TestPresenterResult;

class TestPresenterTesterListener implements IPresenterTesterListener
{
	/** @var bool */
	public $enabled = false;

	/** @var TestPresenterResult|null */
	public $passedResult;


	public function onRequest(TestPresenterRequest $request): TestPresenterRequest
	{
		if (!$this->enabled) {
			return $request;
		}
		return $request->withParameters(['action' => 'render']);
	}


	public function onResult(TestPresenterResult $result): void
	{
		$this->passedResult = $result;
	}
}
