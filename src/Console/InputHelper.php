<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Console;

use Symfony\Component\Console\Input\InputInterface;

final class InputHelper
{
    /**
     * @return string[]
     */
    public static function resolveSkippedTypes(InputInterface $input): array
    {
        $skippedTypes = (array) $input->getOption('skip-type');

        foreach ($skippedTypes as $key => $skippedType) {
            $skippedTypes[$key] = ltrim($skippedType, '\\');
        }

        return $skippedTypes;
    }
}
