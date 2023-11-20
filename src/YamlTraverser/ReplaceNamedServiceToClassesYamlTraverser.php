<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\YamlTraverser;

use TomasVotruba\Tryml\Contract\YamlTraverserInterface;
use TomasVotruba\Tryml\ValueObject\ServiceNameToClassMap;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class ReplaceNamedServiceToClassesYamlTraverser implements YamlTraverserInterface
{
    /**
     * @var string[]
     */
    private readonly array $servicesNamesToReplaceWithClass;

    /**
     * @var array<string, string>
     */
    private array $serviceNamesToClasses = [];

    /**
     * @param string[] $servicesNamesToReplaceWithClass
     */
    public function __construct(
        array $servicesNamesToReplaceWithClass
    ) {
        Assert::allString($servicesNamesToReplaceWithClass);
        $this->servicesNamesToReplaceWithClass = $servicesNamesToReplaceWithClass;
    }

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function traverse(array $yamlFiles): void
    {
        // 1. replace service names by classes
        foreach ($yamlFiles as $yamlFile) {
            $services = $yamlFile->getServices();
            if ($services === []) {
                continue;
            }

            $changedServices = [];

            foreach ($services as $serviceName => $serviceDefinition) {
                if (! in_array($serviceName, $this->servicesNamesToReplaceWithClass, true)) {
                    $changedServices[$serviceName] = $serviceDefinition;
                    continue;
                }

                $serviceClass = $serviceDefinition['class'];

                unset($serviceDefinition['class']);
                // normalize empty service registration
                if ($serviceDefinition === []) {
                    $serviceDefinition = null;
                }

                $changedServices[$serviceClass] = $serviceDefinition;

                $this->serviceNamesToClasses[$serviceName] = $serviceClass;
            }

            $yamlFile->changeYaml(static function (array $yaml) use ($changedServices): array {
                $yaml['services'] = $changedServices;
                return $yaml;
            });
        }
    }

    public function getServiceNameToClassMap(): ServiceNameToClassMap
    {
        return new ServiceNameToClassMap($this->serviceNamesToClasses);
    }
}
