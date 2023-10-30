<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\ValueObject\ServiceNameToClassMap;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class ReplaceRabbitCallbackByTypeYamlTraverser implements YamlTraverserInterface
{
    private ServiceNameToClassMap $serviceNameToClassMap;

    public function __construct(ServiceNameToClassMap $serviceNameToClassMap)
    {
        $this->serviceNameToClassMap = $serviceNameToClassMap;
    }

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        foreach ($yamlFiles as $yamlFile) {
            $yamlFile->changeYaml(function (array $yaml): ?array {
                if (! isset($yaml['old_sound_rabbit_mq']['consumers'])) {
                    return null;
                }

                foreach ($yaml['old_sound_rabbit_mq']['consumers'] as $key => $consumerDefinition) {
                    if (! isset($consumerDefinition['callback'])) {
                        continue;
                    }

                    $callback = $consumerDefinition['callback'];
                    if (! is_string($callback)) {
                        continue;
                    }

                    $serviceType = $this->serviceNameToClassMap->match($callback);
                    if (! is_string($serviceType)) {
                        continue;
                    }

                    $yaml['old_sound_rabbit_mq']['consumers'][$key]['callback'] = $serviceType;
                }

                return $yaml;
            });
        }
    }
}
