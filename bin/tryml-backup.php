<?php

declare(strict_types=1);

// trim your yaml files to the minimum required lines :)

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\FileSystem\YamlPrinter;
use TomasVotruba\Tryml\ServicesResolver;
use TomasVotruba\Tryml\YamlTraverser\AddServicesDefaultsYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceNamedServiceToClassesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceRabbitCallbackByTypeYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceMethodCallByTypesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceNamedArgumentByTypesYamlTraverser;

require __DIR__ . '/../../../vendor/autoload.php';

$symfonyStyle = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());


$yamlFinder = new YamlFinder();
$yamlFiles = $yamlFinder->findYamlFiles([
    __DIR__ . '/../../../src',
    __DIR__ . '/../../../app/config',
]);

//$consoleApplication = new \Symfony\Component\Console\Application();
//$consoleApplication->add();

$registeredServiceNames = ServicesResolver::resolveRegisteredServiceNames($yamlFiles);

$symfonyStyle->title('Registered service names');
$symfonyStyle->listing($registeredServiceNames);

$servicesNamesToSkip = \TomasVotruba\Tryml\SkippedServicesResolver::resolve($yamlFiles);
$servicesNamesToReplaceWithClass = ServicesResolver::resolveServicesNamesToReplace($registeredServiceNames, $servicesNamesToSkip);

$symfonyStyle->title('List of services to replace name by type');

if ($servicesNamesToReplaceWithClass === []) {
    $symfonyStyle->warning('None');
    return;
}

// to notice
$symfonyStyle->listing($servicesNamesToReplaceWithClass);
sleep(2);

// 1. replace named classes by type if possible
$replaceNamedServiceToClassesYamlTraverser = new ReplaceNamedServiceToClassesYamlTraverser($servicesNamesToReplaceWithClass);
$replaceNamedServiceToClassesYamlTraverser->traverse($yamlFiles);

$serviceNameToClassMap = $replaceNamedServiceToClassesYamlTraverser->getServiceNameToClassMap();

// 2. replace argument service names by classes
$replaceServiceNamedArgumentByTypesYamlTraverser = new ReplaceServiceNamedArgumentByTypesYamlTraverser($serviceNameToClassMap);
$replaceServiceNamedArgumentByTypesYamlTraverser->traverse($yamlFiles);

// 3. replace in method calls
$replaceServiceMethodCallByTypesYamlTraverser = new ReplaceServiceMethodCallByTypesYamlTraverser($serviceNameToClassMap);
$replaceServiceMethodCallByTypesYamlTraverser->traverse($yamlFiles);

// 4. replace rabbit consumer names
$replaceRabbitCallbackByTypeYamlTraverser = new ReplaceRabbitCallbackByTypeYamlTraverser($serviceNameToClassMap);
$replaceRabbitCallbackByTypeYamlTraverser->traverse($yamlFiles);

// @todo extract those to a command


// @todo add 2nd comand to remove explicit arguments if the type is listed in configs as a single class


// @todo create private tryml repository and move this there



$addServicesDefaultsYamlTraverser = new AddServicesDefaultsYamlTraverser();
$addServicesDefaultsYamlTraverser->traverse($yamlFiles);

$arrayInput = new \Symfony\Component\Console\Input\ArrayInput($argv);
$isDryRun = (bool) $arrayInput->hasParameterOption('--dry-run');

$yamlPrinter = new YamlPrinter($symfonyStyle);
$yamlPrinter->print($yamlFiles, $isDryRun);
