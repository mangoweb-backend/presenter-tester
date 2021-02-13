<?php declare(strict_types = 1);

namespace MangowebTests\Tester\PresenterTester\Presenters;

use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;

class ExamplePresenter extends Presenter
{

	public function actionRender()
	{
	}

	public function actionError()
	{
		$this->error();
	}

	/**
	 * @crossOrigin
	 */
	public function handleSignal(string $value)
	{
		$this->sendResponse(new TextResponse('signal processed with ' . $value));
	}
}
