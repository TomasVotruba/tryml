<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Tests\FileSystem\YamlFinder;

use PHPUnit\Framework\TestCase;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class YamlFinderTest extends TestCase
{
    public function test(): void
    {
        $yamlFinder = new YamlFinder();

        $yamlFiles = $yamlFinder->findYamlFiles([
            __DIR__ . '/fixture',
        ]);

        $this->assertCount(2, $yamlFiles);
        $this->assertContainsOnlyInstancesOf(YamlFile::class, $yamlFiles);
    }
}
