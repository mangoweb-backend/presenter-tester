<?php declare(strict_types = 1);

$configurator = require __DIR__ . '/bootstrap.configurator.php';
$configurator->addConfig([
	'mango.tester' => [
		'appContainer' => [
			'configs' => [
				'%configDir%/app.presenter-tester.neon',
			],
		],
	],
]);
return $configurator->getContainerFactory();
