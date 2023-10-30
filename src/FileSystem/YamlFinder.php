<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\FileSystem;

use Nette\Utils\FileSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class YamlFinder
{
    /**
     * @param string[] $directories
     * @return YamlFile[]
     */
    public function findYamlFiles(array $directories): array
    {
        Assert::allString($directories);

        $finder = Finder::create()
            ->files()
            ->in($directories)
            ->name('#\.(yml|yaml)#');

        $yamlFiles = [];

        foreach ($finder as $yamlFile) {
            $fileContents = FileSystem::read($yamlFile->getRealPath());

            $yaml = Yaml::parse($fileContents);
            if (! is_array($yaml)) {
                continue;
            }

            $yamlFiles[] = new YamlFile($yamlFile->getRealPath(), $yaml, $fileContents);
        }

        return $yamlFiles;
    }
}
