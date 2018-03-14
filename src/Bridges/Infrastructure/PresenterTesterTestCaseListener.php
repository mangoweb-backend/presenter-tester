<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester\Bridges\Infrastructure;

use Mangoweb\Tester\Infrastructure\ITestCaseListener;
use Mangoweb\Tester\Infrastructure\TestCase;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Tester\Assert;


class PresenterTesterTestCaseListener implements ITestCaseListener
{
	/** @var PresenterTester|NULL */
	public $presenterTester;


	public function setUp(TestCase $testCase): void
	{
	}


	public function tearDown(TestCase $testCase): void
	{
		if (!$this->presenterTester) {
			return;
		}
		foreach ($this->presenterTester->getResults() as $i => $result) {
			if (!$result->wasResponseInspected()) {
				Assert::fail(sprintf('Request #%d to %s presenter was not asserted', $i + 1, $result->getRequest()->getPresenterName()));
			}
		}
	}
}
