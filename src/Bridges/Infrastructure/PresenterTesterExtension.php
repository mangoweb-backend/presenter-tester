<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester\Bridges\Infrastructure;

use Mangoweb\Tester\Infrastructure\MangoTesterExtension;
use Mangoweb\Tester\PresenterTester\PresenterTester;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\User;

class PresenterTesterExtension extends CompilerExtension
{
	public $defaults = [
		'baseUrl' => 'https://test.dev',
		'identityFactory' => null,
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('presenterTester'))
			->setClass(PresenterTester::class)
			->setArguments(['baseUrl' => $config['baseUrl'], 'identityFactory' => $config['identityFactory']])
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
	}


	private function requireService(string $class)
	{
		$builder = $this->getContainerBuilder();
		$name = preg_replace('#\W+#', '_', $class);
		$builder->addDefinition($this->prefix($name))
			->setClass($class)
			->setDynamic()
			->addTag(MangoTesterExtension::TAG_REQUIRE);
	}
}
