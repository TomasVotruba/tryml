<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml;

use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class SkippedServicesResolver
{
    public function __construct(
        private readonly ServicesResolver $servicesResolver,
    ) {
    }

    /**
     * @param YamlFile[] $yamlFiles
     * @param string[] $skipNames
     * @param string[] $skipClasses
     * @return string[]
     */
    public function resolve(array $yamlFiles, array $skipNames, array $skipClasses): array
    {
        Assert::allIsInstanceOf($yamlFiles, YamlFile::class);
        Assert::allString($skipNames);
        Assert::allString($skipClasses);

        $registeredServiceNames = $this->servicesResolver->resolveRegisteredServiceNames($yamlFiles, $skipClasses);
        $multipleTimesRegisteredServices = ArrayUtils::resolveDuplicatedItems($registeredServiceNames);

        $aliasNames = $this->servicesResolver->resolveAliasNames($yamlFiles);
        $ambiguousClassesNames = $this->servicesResolver->resolveAmbiguousClassesNames($yamlFiles);

        return array_merge($multipleTimesRegisteredServices, $aliasNames, $ambiguousClassesNames, $skipNames);
    }
}
