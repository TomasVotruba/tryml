<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\ValueObject\ServiceNameToClassMap;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class ReplaceServiceNamedArgumentByTypesYamlTraverser implements YamlTraverserInterface
{
    public function __construct(
        private readonly ServiceNameToClassMap $serviceNameToClassMap
    ) {
    }

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $serviceName => $serviceDefinition) {
                $yamlFile->changeYamlService($serviceName, function (array $serviceDefinition): ?array {
                    if (! isset($serviceDefinition[ServiceKey::ARGUMENTS])) {
                        return null;
                    }

                    foreach ($serviceDefinition[ServiceKey::ARGUMENTS] as $key => $argument) {
                        if (! is_string($argument)) {
                            continue;
                        }

                        $bareArgument = ltrim($argument, '@');

                        $serviceType = $this->serviceNameToClassMap->match($bareArgument);
                        if (! is_string($serviceType)) {
                            continue;
                        }

                        $serviceDefinition[ServiceKey::ARGUMENTS][$key] = '@' . $serviceType;
                    }

                    return $serviceDefinition;
                });
            }
        }
    }
}
