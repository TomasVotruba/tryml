<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\ValueObject\YamlFile;

/**
 * Add "_defaults" to services config if not added yet
 */
final class AddServicesDefaultsYamlTraverser implements YamlTraverserInterface
{
    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        foreach ($yamlFiles as $yamlFile) {
            if ($yamlFile->getServices() === []) {
                continue;
            }

            $yamlFile->changeYaml(function (array $yaml): ?array {
                // already set
                if (isset($yaml['services'][ServiceKey::DEFAULTS])) {
                    return null;
                }

                // add as first item
                $yaml['services'] = array_merge([
                    ServiceKey::DEFAULTS => [
                        'autowire' => true,
                        'autoconfigure' => true,
                    ],
                ], $yaml['services']);

                return $yaml;
            });
        }
    }
}
