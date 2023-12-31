<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use TomasVotruba\Tryml\DependencyInjection\ContainerFactory;

if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    // project's autoload
    require_once __DIR__ . '/../../../../vendor/autoload.php';
} else {
    // local repository
    require_once __DIR__ . '/../vendor/autoload.php';
}

$containerFactory = new ContainerFactory();
$container = $containerFactory->create();

/** @var Application $application */
$application = $container->make(Application::class);

$exitCode = $application->run(new ArgvInput(), new ConsoleOutput());
exit($exitCode);
