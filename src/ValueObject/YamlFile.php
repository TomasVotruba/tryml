<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\ValueObject;

use TomasVotruba\Tryml\Enum\ServiceKey;
use TomasVotruba\Tryml\FileSystem\StaticRelativeFilePathHelper;
use Webmozart\Assert\Assert;

final class YamlFile
{
    private bool $hasChanged = false;

    private string $filePath;

    /**
     * @var array<string, mixed>
     */
    private array $yaml;

    /**
     * @var mixed[]
     */
    private array $originalYaml;

    private string $originalFileContents;

    /**
     * @param mixed[] $yaml
     */
    public function __construct(
        string $filePath,
        array $yaml,
        string $originalFileContents
    ) {
        $this->filePath = $filePath;

        $this->yaml = $yaml;
        $this->originalYaml = $yaml;

        $this->originalFileContents = $originalFileContents;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getRelativeFilePath(): string
    {
        return StaticRelativeFilePathHelper::resolveFromCwd($this->filePath);
    }

    public function changeYaml(callable $callable): void
    {
        $yaml = $this->getYaml();
        $changedYaml = $callable($yaml);

        if ($changedYaml === null || $changedYaml === $yaml) {
            return;
        }

        Assert::isArray($changedYaml);

        $this->yaml = $changedYaml;
        $this->markAsChanged();
    }

    /**
     * @return mixed[]
     */
    public function getYaml(): array
    {
        return $this->yaml;
    }

    public function markAsChanged(): void
    {
        $this->hasChanged = true;
    }

    public function hasChanged(): bool
    {
        if ($this->yaml === $this->originalYaml) {
            return false;
        }

        return $this->hasChanged;
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getServices(): array
    {
        return $this->yaml['services'] ?? [];
    }

    public function changeYamlService(string $serviceKey, callable $callable): void
    {
        if ($serviceKey === ServiceKey::DEFAULTS) {
            return;
        }

        $serviceDefinition = $this->yaml['services'][$serviceKey] ?? null;
        if (! is_array($serviceDefinition)) {
            return;
        }

        $changedServiceDefinition = $callable($serviceDefinition);
        if ($changedServiceDefinition === null || $serviceDefinition === $changedServiceDefinition) {
            return;
        }

        // retype to null for easier print
        if ($changedServiceDefinition === []) {
            $changedServiceDefinition = null;
        }

        $this->yaml['services'][$serviceKey] = $changedServiceDefinition;
        $this->markAsChanged();
    }

    public function getOriginalFileContents(): string
    {
        return $this->originalFileContents;
    }
}
