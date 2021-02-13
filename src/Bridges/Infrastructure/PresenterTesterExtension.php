<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester\Bridges\Infrastructure;

use Mangoweb\Tester\Infrastructure\MangoTesterExtension;
use Mangoweb\Tester\PresenterTester\IPresenterTesterListener;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\User;

class PresenterTesterExtension extends CompilerExtension
{
	/** @var array */
	public $defaults = [
		'baseUrl' => 'https://test.dev',
		'identityFactory' => null,
	];


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('presenterTester'))
			->setClass(PresenterTester::class)
			->addSetup(new Statement('?->? = ?',
				[
					$this->prefix('@presenterTesterTearDown'),
					'presenterTester',
					'@self',
				]));

		$builder->addDefinition($this->prefix('presenterTesterTearDown'))
			->setClass(PresenterTesterTestCaseListener::class);
		$this->requireService(IPresenterFactory::class);
		$this->requireService(User::class);
		$this->requireService(IRouter::class);
		$this->requireService(IRequest::class);
		$this->requireService(Session::class);
		$this->requireService(Application::class);
	}


	public function beforeCompile(): void
	{
		$config = $this->validateConfig($this->defaults);
		$builder = $this->getContainerBuilder();;
		$definition = $builder->getDefinition($this->prefix('presenterTester'));
		assert($definition instanceof ServiceDefinition);
		$definition->setArguments([
			'baseUrl' => $config['baseUrl'],
			'identityFactory' => $config['identityFactory'],
			'listeners' => $builder->findByType(IPresenterTesterListener::class),
		]);
	}


	private function requireService(string $class): void
	{
		$builder = $this->getContainerBuilder();
		$name = preg_replace('#\W+#', '_', $class);
		$builder->addDefinition($this->prefix($name))
			->setClass($class)
			->addTag(MangoTesterExtension::TAG_REQUIRE);
	}
}
