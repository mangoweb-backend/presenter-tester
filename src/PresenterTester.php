<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester;

use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Security\User;
use Tester\Assert;


class PresenterTester
{
	/** @var Session */
	private $session;

	/** @var IPresenterFactory */
	private $presenterFactory;

	/** @var IRouter */
	private $router;

	/** @var IRequest */
	private $httpRequest;

	/** @var string */
	private $baseUrl;

	/** @var User */
	private $user;

	/** @var callable|NULL */
	private $identityFactory;

	/** @var TestPresenterResult[] */
	private $results = [];


	public function __construct(
		string $baseUrl,
		Session $session,
		IPresenterFactory $presenterFactory,
		IRouter $router,
		IRequest $httpRequest,
		User $user,
		callable $identityFactory = NULL
	) {
		$this->session = $session;

		$this->presenterFactory = $presenterFactory;
		$this->router = $router;
		$this->httpRequest = $httpRequest;
		$this->baseUrl = $baseUrl;
		$this->user = $user;
		$this->identityFactory = $identityFactory;
	}


	public function execute(TestPresenterRequest $testRequest): TestPresenterResult
	{
		$applicationRequest = $this->createApplicationRequest($testRequest);
		$presenter = $this->createPresenter($testRequest);
		if ($applicationRequest->getMethod() === 'GET') {
			$matchedRequest = $this->router->match($this->httpRequest);
			PresenterAssert::assertRequestMatch($applicationRequest, $matchedRequest);
		}

		try {
			$response = $presenter->run($applicationRequest);
			$badRequestException = NULL;
		} catch (BadRequestException $badRequestException) {
			$response = NULL;
		}
		if ($applicationRequest->getParameter(Presenter::SIGNAL_KEY) && method_exists($presenter, 'isSignalProcessed')) {
			if (!$presenter->isSignalProcessed()) {
				$cause = $badRequestException ? 'BadRequestException with code ' . $badRequestException->getCode() . ' and message "' . $badRequestException->getMessage() . '"' : get_class($response);
				Assert::fail('Signal has not been processed at all, received ' . $cause);
			}
		}

		$result = new TestPresenterResult($this->router, $applicationRequest, $presenter, $response, $badRequestException);
		$this->results[] = $result;

		return $result;
	}


	public function createRequest(string $presenterName): TestPresenterRequest
	{
		return new TestPresenterRequest($presenterName, $this->session);
	}


	/**
	 * @return TestPresenterResult[]
	 */
	public function getResults(): array
	{
		return $this->results;
	}


	protected function createPresenter(TestPresenterRequest $request): IPresenter
	{
		$this->loginUser($request);
		$this->setupHttpRequest($request);
		$presenter = $this->presenterFactory->createPresenter($request->getPresenterName());
		if ($presenter instanceof Presenter) {
			$this->setupUIPresenter($presenter);
		}

		return $presenter;
	}


	protected function createApplicationRequest(TestPresenterRequest $testRequest): AppRequest
	{
		return new AppRequest(
			$testRequest->getPresenterName(),
			$testRequest->getPost() ? 'POST' : $testRequest->getMethodName(),
			$testRequest->getParameters(),
			$testRequest->getPost(),
			$testRequest->getFiles()
		);
	}


	protected function loginUser(TestPresenterRequest $request): void
	{
		$this->user->logout(TRUE);
		$identity = $request->getIdentity();
		if (!$identity && $request->shouldHaveIdentity()) {
			if (!$this->identityFactory) {
				throw new \LogicException('identityFactory is not set');
			}
			$identity = ($this->identityFactory)($request);
		}
		if ($identity) {
			$this->user->login($identity);
		}
	}


	protected function setupHttpRequest(TestPresenterRequest $request): void
	{
		$appRequest = $this->createApplicationRequest($request);
		$refUrl = (new UrlScript($this->baseUrl))->setScriptPath('/');
		$url = (new UrlScript($this->router->constructUrl($appRequest, $refUrl)))->setScriptPath('/');

		(function () use ($request, $url) {
			/** @var Request $this */
			if ($request->isAjax()) {
				$this->headers['x-requested-with'] = 'XMLHttpRequest';
			}
			$this->post = $request->getPost();
			$this->url = $url;
			$this->method = $request->getPost() ? 'POST' : 'GET';
		})->bindTo($this->httpRequest, Request::class)->__invoke();
	}


	protected function setupUIPresenter(Presenter $presenter): void
	{
		$presenter->autoCanonicalize = FALSE;
		$presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;
	}

}
