<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Analyzer;

final class ArgumentDefinitionAnalyzer
{
    /**
     * @param array<string, mixed> $serviceDefinition
     * @param string[] $classesToSkip
     */
    public function hasFullyAutowireabeArguments(array $serviceDefinition, array $classesToSkip): bool
    {
        // we need to check first
        if (! isset($serviceDefinition['arguments'])) {
            return false;
        }

        foreach ($serviceDefinition['arguments'] as $key => $value) {
            if (! is_string($value)) {
                return false;
            }

            // named key => skip whole service
            if (is_string($key)) {
                return false;
            }

            // not a service reference
            if (str_starts_with('@', $value)) {
                return false;
            }

            $type = trim($value, '@');

            // first letter should be an upper one, otherwise probably not a class type
            if (! ctype_upper($type[0])) {
                return false;
            }

            if (in_array($type, $classesToSkip, true)) {
                return false;
            }
        }

        return true;
    }
}
