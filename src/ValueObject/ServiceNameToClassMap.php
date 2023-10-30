<?php

namespace TomasVotruba\Tryml\ValueObject;

use Webmozart\Assert\Assert;

final class ServiceNameToClassMap
{
    /**
     * @var array<string, string>
     */
    private array $serviceNameToClassMap;

    /**
     * @param array<string, string> $serviceNameToClassMap
     */
    public function __construct(array $serviceNameToClassMap)
    {
        Assert::allString($serviceNameToClassMap);
        Assert::allString(array_keys($serviceNameToClassMap));

        $this->serviceNameToClassMap = $serviceNameToClassMap;
    }

    public function match(string $serviceName): ?string
    {
        return $this->serviceNameToClassMap[$serviceName] ?? null;
    }
}
