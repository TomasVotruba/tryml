<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\FileSystem;

use Nette\Utils\FileSystem;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use TomasVotruba\Tryml\TrymlDiffer;
use TomasVotruba\Tryml\ValueObject\YamlFile;

final class YamlPrinter
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly TrymlDiffer $trymlDiffer,
    ) {
    }

    /**
     * @param YamlFile[] $yamlFiles
     */
    public function print(array $yamlFiles, bool $isDryRun): void
    {
        foreach ($yamlFiles as $i => $yamlFile) {
            if (! $yamlFile->hasChanged()) {
                continue;
            }

            $this->symfonyStyle->title(sprintf('%d) %s', $i + 1, $yamlFile->getRelativeFilePath()));

            $changedYamlContents = Yaml::dump($yamlFile->getYaml(), 4, 2, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE);

            $consoleFormattedDiff = $this->trymlDiffer->diffForConsole(
                $yamlFile->getOriginalFileContents(),
                $changedYamlContents
            );
            $this->symfonyStyle->writeln($consoleFormattedDiff);

            if (! $isDryRun) {
                FileSystem::write($yamlFile->getFilePath(), $changedYamlContents);
            }

            $this->symfonyStyle->newLine(2);
        }
    }
}
