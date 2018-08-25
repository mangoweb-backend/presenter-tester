<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester;

interface IPresenterTesterListener
{
	public function onRequest(TestPresenterRequest $request): TestPresenterRequest;

	public function onResult(TestPresenterResult $result): void;
}
