<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\Exception\ShouldNotHappenException;
use TomasVotruba\Tryml\Reflection\ConstructorParameterNamesResolver;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class TrimArgumentsYamlTraverser implements YamlTraverserInterface
{
    /**
     * @var string[]
     */
    private const ALWAYS_KNOWN_SERVICE_NAMES = ['@form.factory', '@jms_serializer', '@event_dispatcher', '@doctrine'];

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        // 1. replace service names by classes
        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $serviceName => $serviceDefinition) {
                // resolve constructor parameter names
                $parameterNames = ConstructorParameterNamesResolver::resolve($serviceName);
                if ($parameterNames === []) {
                    continue;
                }

                $yamlFile->changeYamlService($serviceName, function (array $serviceDefinition) use (
                    $parameterNames,
                    $serviceName
                ): ?array {
                    if ($this->shouldSkipServiceDefinition($serviceDefinition)) {
                        return null;
                    }

                    return $this->trimServiceNames($serviceDefinition, $parameterNames, $serviceName);
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

    /**
     * @param array<string, mixed> $serviceDefinition
     * @param string[] $parameterNames
     * @return array<string, mixed>|null
     */
    private function trimServiceNames(array $serviceDefinition, array $parameterNames, string $serviceName): ?array
    {
        foreach ($serviceDefinition[ServiceKey::ARGUMENTS] as $key => $value) {
            // some weird setup
            if (! is_string($value)) {
                return null;
            }

            // already used named argument
            if (is_string($key)) {
                return null;
            }

            // is most likely type => remove
            unset($serviceDefinition[ServiceKey::ARGUMENTS][$key]);
            if ($this->isTypeReference($value)) {
                continue;
            }

            if ($this->isKnownAutowiredName($value)) {
                continue;
            }

            if (! isset($parameterNames[$key])) {
                throw new ShouldNotHappenException(sprintf(
                    'Class "%s" configuration is using extra type in %d',
                    $serviceName,
                    $key
                ));
            }

            // replace implicit argument with explicit one
            $parameterName = $parameterNames[$key];
            Assert::notEmpty($parameterName);

            $serviceDefinition[ServiceKey::ARGUMENTS]['$' . $parameterName] = $value;
        }

        return $serviceDefinition;
    }
}
