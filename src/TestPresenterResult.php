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
use Nette\Forms\Form;
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

	/** @var BadRequestException|NULL */
	private $badRequestException;

	/** @var bool */
	private $responseInspected = FALSE;


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
		assert($this->response !== NULL);
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
		assert($this->badRequestException !== NULL);
		return $this->badRequestException;
	}



	public function assertHasResponse(string $type = NULL): self
	{
		$this->responseInspected = TRUE;
		Assert::type($type ?? IResponse::class, $this->response);

		return $this;
	}


	/**
	 * @param string|array|NULL $match
	 */
	public function assertRenders($match = NULL): self
	{
		$this->responseInspected = TRUE;
		if (is_array($match)) {
			$match = '%A?%' . implode('%A?%', $match) . '%A?%';
		}
		assert(is_string($match) || $match === NULL);
		$response = $this->getTextResponse();
		$source = $response->getSource();
		$source = is_object($source) ? $source->__toString(TRUE) : (string) $source;
		Assert::type('string', $source);
		if ($match !== NULL) {
			Assert::match($match, $source);
		}
		return $this;
	}


	/**
	 * @param array|object|NULL $expected
	 */
	public function assertJson($expected = NULL): self
	{
		$this->responseInspected = TRUE;
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
		$this->responseInspected = TRUE;
		$response = $this->getRedirectResponse();
		$url = $response->getUrl();

		$httpRequest = new HttpRequest(new UrlScript($url, '/'));
		$result = $this->router->match($httpRequest);
		PresenterAssert::assertRequestMatch(new Request($presenterName, NULL, $parameters), $result);

		return $this;
	}


	public function assertRedirectsUrl(string $url): self
	{
		$this->responseInspected = TRUE;
		$response = $this->getRedirectResponse();
		Assert::match($url, $response->getUrl());

		return $this;
	}


	public function assertFormValid(string $formName): self
	{
		$this->responseInspected = TRUE;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, FALSE);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);
		if ($form->hasErrors()) {
			$errors = $form->getErrors();
			Assert::fail(
				"Form has errors: \n" . implode("\n", $errors),
				$errors, []
			);
		}
		return $this;
	}


	public function assertFormHasErrors(string $formName): self
	{
		$this->responseInspected = TRUE;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, FALSE);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);
		Assert::true($form->hasErrors());

		return $this;
	}


	public function assertBadRequest(int $code = NULL, string $messagePattern = NULL)
	{
		$this->responseInspected = TRUE;
		Assert::type(BadRequestException::class, $this->badRequestException);
		assert($this->badRequestException !== NULL);

		if ($code !== NULL) {
			Assert::same($code, $this->badRequestException->getHttpCode());
		}

		if ($messagePattern !== NULL) {
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
