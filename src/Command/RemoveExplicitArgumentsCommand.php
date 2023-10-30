<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\ArrayUtils;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\FileSystem\YamlPrinter;
use TomasVotruba\Tryml\ServicesResolver;
use TomasVotruba\Tryml\SkippedServicesResolver;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class RemoveExplicitArgumentsCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly YamlFinder $yamlFinder,
        private readonly YamlPrinter $yamlPrinter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('remove-explicit-arguments');
        $this->setDescription('Remove explicit typed arguments, that are unique and autowirable');

        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Paths to directories with YAML files');

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without changing the files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directoryPaths = $this->getDirectoryPaths($input);

        $isDryRun = (bool) $input->getOption('dry-run');
        $yamlFiles = $this->yamlFinder->findYamlFiles($directoryPaths);

        $this->symfonyStyle->note(sprintf('Found %d YAML files', count($yamlFiles)));


        // those types should be skipped, as used in multiple services with different names
        $ambiguousClassNames = $this->resolveAmbiguousClassTypes($yamlFiles);

        foreach ($yamlFiles as $yamlFile) {
            if ($yamlFile->getServices() === []) {
                continue;
            }

            $this->symfonyStyle->note(sprintf('Processing "%s" file', $yamlFile->getRelativeFilePath()));

            foreach ($yamlFile->getServices() as $serviceName => $serviceDefinition) {
                if (! isset($serviceDefinition['arguments'])) {
                    // nothing to improve
                    continue;
                }

                $yamlFile->changedYamlService($serviceName, function (array $serviceDefinition) use ($ambiguousClassNames): ?array {
                    // @todo detect unique types here
                    foreach ($serviceDefinition['arguments'] as $key => $value) {
                        if (! is_string($value)) {
                            continue;
                        }

                        // named key => skip whole service
                        if (is_string($key)) {
                            return null;
                        }

                        // not references
                        if (str_starts_with('@', $value)) {
                            continue;
                        }

                        $type = trim($value, '@');

                        // first letter should be an upper one, otherwise probably not a class type
                        if (! ctype_upper($type[0])) {
                            continue;
                        }

                        if (in_array($type, $ambiguousClassNames, true)) {
                            continue;
                        }

                        // here we can remove the the type probably
                        var_dump('remove key: ' . $key);
                    }

                    return $serviceDefinition;
                });
            }
        }

        $this->yamlPrinter->print($yamlFiles, $isDryRun);

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getDirectoryPaths(InputInterface $input): array
    {
        $paths = (array) $input->getArgument('paths');
        Assert::allString($paths);
        Assert::allFileExists($paths);

        return $paths;
    }

    /**
     * @param YamlFile[] $yamlFiles
     * @return string[]
     */
    private function resolveAmbiguousClassTypes(array $yamlFiles): array
    {
        $serviceClasses = [];

        foreach ($yamlFiles as $yamlFile) {
            foreach ($yamlFile->getServices() as $service) {
                if (! isset($service['class'])) {
                    continue;
                }

                $serviceClasses[] = $service['class'];
            }
        }

        return ArrayUtils::resolveDuplicatedItems($serviceClasses);
    }
}
