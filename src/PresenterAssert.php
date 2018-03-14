<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester;

use Nette\Application\Request;
use Nette\StaticClass;
use Tester\Assert;


class PresenterAssert
{
	use StaticClass;


	public static function assertRequestMatch(Request $expected, ?Request $actual, bool $onlyIntersectedParameters = true): void
	{
		Assert::notSame(null, $actual);
		assert($actual !== null);
		Assert::same($expected->getPresenterName(), $actual->getPresenterName());
		$expectedParameters = $expected->getParameters();
		$actualParameters = $actual->getParameters();
		foreach ($actualParameters as $key => $actualParameter) {
			if (!isset($expectedParameters[$key])) {
				if ($onlyIntersectedParameters) {
					continue;
				}
				Assert::fail("Parameter $key not expected");
			}
			$expectedParameter = $expectedParameters[$key];
			if (is_string($actualParameter) && !is_string($expectedParameter)) {
				$expectedParameter = (string) $expectedParameter;
			}
			Assert::same($actualParameter, $expectedParameter, $key);
		}
	}

}
