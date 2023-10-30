<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml;

use TomasVotruba\Tryml\Enum\Skipped;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class SkippedServicesResolver
{
    /**
     * @param YamlFile[] $yamlFiles
     * @return string[]
     */
    public static function resolve(array $yamlFiles): array
    {
        $registeredServiceNames = ServicesResolver::resolveRegisteredServiceNames($yamlFiles);
        $multipleTimesRegisteredServices = ArrayUtils::resolveDuplicatedItems($registeredServiceNames);

        $aliasNames = ServicesResolver::resolveAliasNames($yamlFiles);
        $ambiguousClassesNames = ServicesResolver::resolveAmbiguousClassesNames($yamlFiles);

        return array_merge($multipleTimesRegisteredServices, $aliasNames, $ambiguousClassesNames, Skipped::SKIPPED_SERVICE_NAMES);
    }
}
