<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester;

use Nette\Forms\Controls\CsrfProtection;
use Nette\Http\Session;
use Nette\Security\IIdentity;
use Nette\SmartObject;
use Nextras\Application\UI\Helpers as NextrasSecuredHelpers;


/**
 * Immutable object
 */
class TestPresenterRequest
{
	use SmartObject;

	/** @var string */
	private $methodName = 'GET';

	/** @var string */
	private $presenterName;

	/** @var array */
	private $parameters = [];

	/** @var array */
	private $post = [];

	/** @var array */
	private $files = [];

	/** @var bool */
	private $ajax = FALSE;

	/** @var NULL|string */
	private $componentClass;

	/** @var bool */
	private $shouldHaveIdentity = FALSE;

	/** @var IIdentity|NULL */
	private $identity;

	/** @var Session */
	private $session;


	public function __construct(string $presenterName, Session $session)
	{
		$this->presenterName = $presenterName;
		$this->session = $session;
	}


	public function getMethodName(): string
	{
		return $this->methodName;
	}


	public function getPresenterName(): string
	{
		return $this->presenterName;
	}


	public function getParameters(): array
	{
		return $this->parameters + ['action' => 'default'];
	}


	public function getPost(): array
	{
		return $this->post;
	}


	public function getFiles(): array
	{
		return $this->files;
	}


	public function isAjax(): bool
	{
		return $this->ajax;
	}


	public function getComponentClass(): ?string
	{
		return $this->componentClass;
	}


	public function shouldHaveIdentity(): bool
	{
		return $this->shouldHaveIdentity;
	}


	public function getIdentity(): ?IIdentity
	{
		return $this->identity;
	}


	/**
	 * @param string      $signal
	 * @param array       $componentParameters
	 * @param string|NULL $componentClass required for a secured signal
	 * @return TestPresenterRequest
	 */
	public function withSignal(string $signal, array $componentParameters = [], string $componentClass = NULL): TestPresenterRequest
	{
		assert(!isset($this->parameters['do']));
		$request = clone $this;
		$request->componentClass = $componentClass;
		$request->parameters['do'] = $signal;
		$lastDashPosition = strrpos($signal, '-');
		$componentName = $lastDashPosition !== FALSE ? substr($signal, 0, $lastDashPosition) : '';

		if ($componentClass && class_exists(NextrasSecuredHelpers::class)) {
			$csrfToken = NextrasSecuredHelpers::getCsrfToken(
				$this->session,
				$componentClass,
				'handle' . lcfirst(substr($signal, $lastDashPosition ? $lastDashPosition + 1 : 0)),
				[$componentName, array_map(function ($param) {
					return is_object($param) && method_exists($param, 'getId') ? $param->getId() : $param;
				}, $componentParameters)]
			);
			$componentParameters['_sec'] = $csrfToken;
		}

		if ($componentName !== '') {
			$newParameters = [];
			foreach ($componentParameters as $key => $value) {
				$newParameters["$componentName-$key"] = $value;
			}
			$componentParameters = $newParameters;
		}

		$request->parameters = $componentParameters + $request->parameters;

		return $request;
	}


	public function withMethod(string $methodName): TestPresenterRequest
	{
		$request = clone $this;
		$request->methodName = $methodName;

		return $request;
	}


	public function withForm(string $formName, array $post, array $files = [], bool $withProtection = TRUE): TestPresenterRequest
	{
		$request = $this->withSignal("$formName-submit");
		if ($withProtection) {
			assert($this->session instanceof \Mangoweb\Tester\Infrastructure\Mocks\Session || $this->session instanceof \Kdyby\FakeSession\Session);
			$this->session->setFakeId('mango.id');
			$this->session->getSection(CsrfProtection::class)->token = 'mango.token';
			$post = $post + ['_token_' => 'abcdefghijbc2dP4jNcgTMfjnxHe6Gj/Kbzpk='];
		}
		$request->post = $post;
		$request->files = $files;

		return $request;
	}


	public function withAjax(bool $enable = TRUE): TestPresenterRequest
	{
		$request = clone $this;
		$request->ajax = $enable;

		return $request;
	}


	public function withParameters(array $parameters): TestPresenterRequest
	{
		$request = clone $this;
		$request->parameters = $parameters + $this->parameters;

		return $request;
	}


	public function withPost(array $post): TestPresenterRequest
	{
		$request = clone $this;
		$request->post = $post + $this->post;

		return $request;
	}


	public function withFiles(array $files): TestPresenterRequest
	{
		$request = clone $this;
		$request->files = $files + $this->files;

		return $request;
	}


	public function withIdentity(IIdentity $identity = NULL): TestPresenterRequest
	{
		$request = clone $this;
		$request->shouldHaveIdentity = TRUE;
		$request->identity = $identity;

		return $request;
	}

}
