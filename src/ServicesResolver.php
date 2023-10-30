<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml;

use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class ServicesResolver
{
    /**
     * @param YamlFile[] $yamlFiles
     * @param string[] $skipClasses
     * @return string[]
     */
    public function resolveRegisteredServiceNames(array $yamlFiles, array $skipClasses): array
    {
        $registeredServiceNames = [];

        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $serviceName => $serviceDefinition) {
                // skip default services
                if ($serviceName === ServiceKey::DEFAULTS) {
                    continue;
                }

                // this is most likely named service
                if ($serviceName !== strtolower($serviceName)) {
                    continue;
                }

                $currentClass = $serviceDefinition['class'] ?? null;

                // skip excluded classes
                if (is_string($currentClass) && in_array($currentClass, $skipClasses, true)) {
                    continue;
                }

                $registeredServiceNames[] = $serviceName;
            }
        }

        sort($registeredServiceNames);

        return $registeredServiceNames;
    }

    /**
     * @param YamlFile[] $yamlFiles
     * @return string[]
     */
    public function resolveAliasNames(array $yamlFiles): array
    {
        $aliases = [];

        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $serviceDefinition) {
                if (! isset($serviceDefinition['alias'])) {
                    continue;
                }

                $aliases[] = $serviceDefinition['alias'];
            }
        }

        return $aliases;
    }

    /**
     * @param string[] $serviceNames
     * @param string[] $servicesNamesToSkip
     *
     * @return string[]
     */
    public function resolveServicesNamesToReplace(array $serviceNames, array $servicesNamesToSkip): array
    {
        Assert::allString($serviceNames);
        Assert::allString($servicesNamesToSkip);

        return array_filter($serviceNames, function (string $serviceName) use ($servicesNamesToSkip): bool {
            return ! in_array($serviceName, $servicesNamesToSkip, true);
        });
    }

    /**
     * @param YamlFile[] $yamlFiles
     * @return string[]
     */
    public function resolveAmbiguousClassesNames(array $yamlFiles): array
    {
        $serviceClasses = [];

        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $service) {
                if (! isset($service['class'])) {
                    continue;
                }

                $serviceClasses[] = $service['class'];
            }
        }

        $ambiguousClassNames = ArrayUtils::resolveDuplicatedItems($serviceClasses);

        $ambiguousServiceNames = [];

        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $serviceName => $service) {
                if (! isset($service['class'])) {
                    continue;
                }

                if (! in_array($service['class'], $ambiguousClassNames, true)) {
                    continue;
                }

                $ambiguousServiceNames[] = $serviceName;
            }
        }

        sort($ambiguousServiceNames);

        return array_unique($ambiguousServiceNames);
    }
}
