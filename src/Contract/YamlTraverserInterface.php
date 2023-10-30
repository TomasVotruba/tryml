<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Contract;

use TomasVotruba\Tryml\ValueObject\YamlFile;

interface YamlTraverserInterface
{
    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void;
}
