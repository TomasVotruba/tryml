<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Application;

use TomasVotruba\Tryml\ValueObject\YamlFile;
use TomasVotruba\Tryml\YamlTraverser\AddServicesDefaultsYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceNamedServiceToClassesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceMethodCallByTypesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceNamedArgumentByTypesYamlTraverser;

final class NamedServicesYamlProcessor
{
    /**
     * @param array<string, string> $servicesNamesToReplaceWithClass
     * @param YamlFile[] $yamlFiles
     */
    public function processYamlFiles(array $servicesNamesToReplaceWithClass, array $yamlFiles): void
    {
        // 1. replace named classes by type if possible
        $replaceNamedServiceToClassesYamlTraverser = new ReplaceNamedServiceToClassesYamlTraverser(
            $servicesNamesToReplaceWithClass
        );
        $replaceNamedServiceToClassesYamlTraverser->traverse($yamlFiles);

        $serviceNameToClassMap = $replaceNamedServiceToClassesYamlTraverser->getServiceNameToClassMap();

        // 2. replace argument service names by classes
        $replaceServiceNamedArgumentByTypesYamlTraverser = new ReplaceServiceNamedArgumentByTypesYamlTraverser(
            $serviceNameToClassMap
        );
        $replaceServiceNamedArgumentByTypesYamlTraverser->traverse($yamlFiles);

        // 3. replace in method calls
        $replaceServiceMethodCallByTypesYamlTraverser = new ReplaceServiceMethodCallByTypesYamlTraverser(
            $serviceNameToClassMap
        );
        $replaceServiceMethodCallByTypesYamlTraverser->traverse($yamlFiles);

        $addServicesDefaultsYamlTraverser = new AddServicesDefaultsYamlTraverser();
        $addServicesDefaultsYamlTraverser->traverse($yamlFiles);
    }
}
