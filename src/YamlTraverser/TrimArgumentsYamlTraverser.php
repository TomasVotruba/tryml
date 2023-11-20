<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\Reflection\ConstructorParameterNamesResolver;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class TrimArgumentsYamlTraverser implements YamlTraverserInterface
{
    /**
     * @var string[]
     */
    private const ALWAYS_KNOWN_SERVICE_NAMES = ['@form.factory', '@jms_serializer'];

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        // 1. replace service names by classes
        foreach ($yamlFiles as $yamlFile) {
            $services = $yamlFile->getServices();
            if ($services === []) {
                continue;
            }

            foreach ($services as $serviceName => $serviceDefinition) {
                // the class must be autoloadable to get param names
                if (! class_exists($serviceName)) {
                    continue;
                }

                // resolve constructor parameter names
                $parameterNames = ConstructorParameterNamesResolver::resolve($serviceName);
                if ($parameterNames === []) {
                    continue;
                }

                $yamlFile->changeYamlService($serviceName, function (array $serviceDefinition) use (
                    $parameterNames
                ) {
                    if ($this->shouldSkipServiceDefinition($serviceDefinition)) {
                        return null;
                    }

                    foreach ($serviceDefinition[ServiceKey::ARGUMENTS] as $key => $value) {
                        // some weird setup
                        if (! is_string($value)) {
                            return null;
                        }

                        if ($this->isTypeReference($value)) {
                            // is most likely type => remove
                            unset($serviceDefinition['arguments'][$key]);
                            continue;
                        }

                        if ($this->isKnownAutowiredName($value)) {
                            unset($serviceDefinition['arguments'][$key]);
                            continue;
                        }

                        // replace implicit argument with explicit one
                        $parameterName = $parameterNames[$key];
                        unset($serviceDefinition['arguments'][$key]);
                        $serviceDefinition['arguments']['$' . $parameterName] = $value;
                    }

                    return $serviceDefinition;
                });
            }
        }
    }

    /**
     * @param array<string, mixed> $serviceDefinition
     */
    private function shouldSkipServiceDefinition(array $serviceDefinition): bool
    {
        if (! isset($serviceDefinition[ServiceKey::ARGUMENTS])) {
            return true;
        }

        if (isset($serviceDefinition[ServiceKey::DECORATES])) {
            return true;
        }

        return isset($serviceDefinition[ServiceKey::FACTORY]);
    }

    private function isTypeReference(string $value): bool
    {
        if (! str_starts_with($value, '@')) {
            return false;
        }

        return ctype_upper($value[1]);
    }

    private function isKnownAutowiredName(string $value): bool
    {
        return in_array($value, self::ALWAYS_KNOWN_SERVICE_NAMES, true);
    }
}
