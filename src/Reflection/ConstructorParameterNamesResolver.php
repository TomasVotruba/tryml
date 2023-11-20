<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Reflection;

use ReflectionClass;
use ReflectionMethod;
use TomasVotruba\Tryml\Enum\MethodName;
use Webmozart\Assert\Assert;

final class ConstructorParameterNamesResolver
{
    /**
     * @return string[]
     */
    public static function resolve(string $className): array
    {
        if (! class_exists($className)) {
            return [];
        }

        $serviceReflectionClass = new ReflectionClass($className);
        if (! $serviceReflectionClass->hasMethod(MethodName::CONSTRUCTOR)) {
            return [];
        }

        /** @var ReflectionMethod $constructClassMethod */
        $constructClassMethod = $serviceReflectionClass->getConstructor();

        $parameterNames = [];
        foreach ($constructClassMethod->getParameters() as $reflectionParameter) {
            $parameterNames[] = $reflectionParameter->getName();
        }

        Assert::allString($parameterNames);

        return $parameterNames;
    }
}
