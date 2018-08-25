<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new \Mangoweb\Tester\Infrastructure\InfrastructureConfigurator(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config/infrastructure.neon');
$configurator->setupTester();
$configurator->addParameters(['configDir' => __DIR__ . '/config']);

return $configurator;
