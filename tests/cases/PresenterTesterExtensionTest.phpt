<?php declare(strict_types = 1);

namespace MangowebTests\Tester\PresenterTester;

use Mangoweb\Tester\Infrastructure\InfrastructureConfigurator;
use Mangoweb\Tester\Infrastructure\TestCase;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Tester\Assert;

$appConfigurator = require __DIR__ . '/../bootstrap.configurator.php';
assert($appConfigurator instanceof InfrastructureConfigurator);


/**
 * @testCase
 */
class PresenterTesterExtensionTest extends TestCase
{

	/** @var PresenterTester */
	private $presenterTester;

	/** @var TestPresenterTesterListener */
	private $listener;


	public function __construct(PresenterTester $presenterTester, TestPresenterTesterListener $listener)
	{
		$this->presenterTester = $presenterTester;
		$this->listener = $listener;
	}


	public function testExtension()
	{
		$rpBaseUrl = new \ReflectionProperty(PresenterTester::class, 'baseUrl');
		$rpBaseUrl->setAccessible(true);
		Assert::same('http://mango.dev', $rpBaseUrl->getValue($this->presenterTester));

		$rpListeners = new \ReflectionProperty(PresenterTester::class, 'listeners');
		$rpListeners->setAccessible(true);
		Assert::same([$this->listener], array_values($rpListeners->getValue($this->presenterTester)));
	}
}


$appConfigurator->addConfig(__DIR__ . '/extension.config.neon');
$factory = $appConfigurator->getContainerFactory();

PresenterTesterExtensionTest::run($factory);
