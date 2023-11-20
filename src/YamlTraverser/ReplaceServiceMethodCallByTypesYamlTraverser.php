<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\ValueObject\ServiceNameToClassMap;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class ReplaceServiceMethodCallByTypesYamlTraverser implements YamlTraverserInterface
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
            foreach ($yamlFile->getServices() as $serviceKey => $serviceDefinition) {
                $yamlFile->changeYamlService($serviceKey, function (array $serviceDefinition): ?array {
                    if (! isset($serviceDefinition['calls'])) {
                        return null;
                    }

                    foreach ($serviceDefinition['calls'] as $callKey => $call) {
                        if (! isset($call[1][0])) {
                            continue;
                        }

                        if (! is_string($call[1][0])) {
                            continue;
                        }

                        $bareArgument = ltrim($call[1][0], '@');
                        $serviceType = $this->serviceNameToClassMap->match($bareArgument);

                        if (! is_string($serviceType)) {
                            continue;
                        }

                        $serviceDefinition['calls'][$callKey][1][0] = '@' . $serviceType;
                    }

                    return $serviceDefinition;
                });
            }
        }
    }
}
