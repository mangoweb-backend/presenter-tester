<?php declare(strict_types = 1);

namespace Mangoweb\Tester\PresenterTester;

use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\Component;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Tester\Assert;


class TestPresenterResult
{
	/** @var IRouter */
	private $router;

	/** @var IPresenter */
	private $presenter;

	/** @var Request */
	private $request;

	/** @var IResponse|NULL */
	private $response;

	/** @var string|NULL */
	private $textResponseSource;

	/** @var BadRequestException|NULL */
	private $badRequestException;

	/** @var bool */
	private $responseInspected = false;


	public function __construct(IRouter $router, Request $request, IPresenter $presenter, ?IResponse $response, ?BadRequestException $badRequestException)
	{
		$this->presenter = $presenter;
		$this->response = $response;
		$this->router = $router;
		$this->badRequestException = $badRequestException;
		$this->request = $request;
	}


	public function getRequest(): Request
	{
		return $this->request;
	}


	public function getPresenter(): IPresenter
	{
		return $this->presenter;
	}


	public function getUIPresenter(): Presenter
	{
		Assert::type(Presenter::class, $this->presenter);
		assert($this->presenter instanceof Presenter);
		return $this->presenter;
	}


	public function getResponse(): IResponse
	{
		Assert::null($this->badRequestException);
		assert($this->response !== null);
		return $this->response;
	}


	public function getRedirectResponse(): RedirectResponse
	{
		$response = $this->getResponse();
		Assert::type(RedirectResponse::class, $response);
		assert($response instanceof RedirectResponse);
		return $response;
	}


	public function getTextResponse(): TextResponse
	{
		$response = $this->getResponse();
		Assert::type(TextResponse::class, $response);
		assert($response instanceof TextResponse);
		return $response;
	}


	public function getTextResponseSource(): string
	{
		if (!$this->textResponseSource) {
			$source = $this->getTextResponse()->getSource();
			$this->textResponseSource = is_object($source) ? $source->__toString(true) : (string) $source;
			Assert::type('string', $this->textResponseSource);
		}
		return $this->textResponseSource;
	}


	public function getJsonResponse(): JsonResponse
	{
		$response = $this->getResponse();
		Assert::type(JsonResponse::class, $response);
		assert($response instanceof JsonResponse);
		return $response;
	}


	public function getBadRequestException(): BadRequestException
	{
		Assert::null($this->response);
		assert($this->badRequestException !== null);
		return $this->badRequestException;
	}


	public function assertHasResponse(string $type = null): self
	{
		$this->responseInspected = true;
		Assert::type($type ?? IResponse::class, $this->response);

		return $this;
	}


	/**
	 * @param string|array|NULL $match
	 */
	public function assertRenders($match = null): self
	{
		$this->responseInspected = true;
		if (is_array($match)) {
			$match = '%A?%' . implode('%A?%', $match) . '%A?%';
		}
		assert(is_string($match) || $match === null);
		$source = $this->getTextResponseSource();
		if ($match !== null) {
			Assert::match($match, $source);
		}
		return $this;
	}


	/**
	 * @param string|array $matches
	 */
	public function assertNotRenders($matches): self
	{
		if (is_string($matches)) {
			$matches = [$matches];
		}
		assert(is_array($matches));
		$this->responseInspected = true;
		$source = $this->getTextResponseSource();
		foreach ($matches as $match) {
			assert(is_string($match));
			$match = "%A%$match%A%";
			if (Assert::isMatching($match, $source)) {
				[$pattern, $actual] = Assert::expandMatchingPatterns($match, $source);
				Assert::fail('%1 should NOT match %2', $actual, $pattern);
			}
		}
		return $this;
	}


	/**
	 * @param array|object|NULL $expected
	 */
	public function assertJson($expected = null): self
	{
		$this->responseInspected = true;
		$response = $this->getJsonResponse();
		if (func_num_args() !== 0) {
			Assert::equal($expected, $response->getPayload());
		}
		return $this;
	}


	/**
	 * @param array $parameters optional parameters, extra parameters in a redirect request are ignored
	 */
	public function assertRedirects(string $presenterName, array $parameters = []): self
	{
		$this->responseInspected = true;
		$response = $this->getRedirectResponse();
		$url = $response->getUrl();

		$httpRequest = new HttpRequest(new UrlScript($url, '/'));
		$result = $this->router->match($httpRequest);
		PresenterAssert::assertRequestMatch(new Request($presenterName, null, $parameters), $result);

		return $this;
	}


	public function assertRedirectsUrl(string $url): self
	{
		$this->responseInspected = true;
		$response = $this->getRedirectResponse();
		Assert::match($url, $response->getUrl());

		return $this;
	}


	public function assertFormValid(string $formName): self
	{
		$this->responseInspected = true;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, false);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);
		if ($form->hasErrors()) {
			$controls = $form->getComponents(true, IControl::class);
			$errorsStr = [];
			foreach ($form->getOwnErrors() as $error) {
				$errorsStr[] = "\town error: " . $error;
			}
			foreach ($controls as $control) {
				assert($control instanceof Component && $control instanceof IControl);
				$errors = $control->getErrors();
				foreach ($errors as $error) {
					$errorsStr[] = "\t" . $control->lookupPath(Form::class) . ": " . $error;
				}
			}
			Assert::fail(
				"Form has errors: \n" . implode("\n", $errorsStr) . "\n",
				$form->getErrors(), []
			);
		}
		return $this;
	}


	public function assertFormHasErrors(string $formName, ?array $formErrors = null): self
	{
		$this->responseInspected = true;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, false);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);
		Assert::true($form->hasErrors());

		if ($formErrors !== null) {
			Assert::same($formErrors, $form->getErrors());
		}

		return $this;
	}


	public function assertBadRequest(int $code = null, string $messagePattern = null)
	{
		$this->responseInspected = true;
		Assert::type(BadRequestException::class, $this->badRequestException);
		assert($this->badRequestException !== null);

		if ($code !== null) {
			Assert::same($code, $this->badRequestException->getHttpCode());
		}

		if ($messagePattern !== null) {
			Assert::match($messagePattern, $this->badRequestException->getMessage());
		}

		return $this;
	}


	/**
	 * @internal
	 */
	public function wasResponseInspected(): bool
	{
		return $this->responseInspected;
	}

}
