<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\Analyzer\ArgumentDefinitionAnalyzer;
use TomasVotruba\Tryml\ArrayUtils;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\FileSystem\YamlPrinter;
use TomasVotruba\Tryml\ValueObject\YamlFile;
use Webmozart\Assert\Assert;

final class RemoveExplicitArgumentsCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly YamlFinder $yamlFinder,
        private readonly YamlPrinter $yamlPrinter,
        private readonly ArgumentDefinitionAnalyzer $argumentDefinitionAnalyzer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('remove-explicit-arguments');
        $this->setDescription('Remove explicit typed arguments, that are unique and autowirable');

        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Paths to directories with YAML files');

        $this->addOption(
            'skip-type',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Types to skip from replacing'
        );
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without changing the files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directoryPaths = $this->getDirectoryPaths($input);

        $skipTypes = (array) $input->getOption('skip-type');
        $isDryRun = (bool) $input->getOption('dry-run');
        $yamlFiles = $this->yamlFinder->findYamlFiles($directoryPaths);

        $this->symfonyStyle->note(sprintf('Found %d YAML files', count($yamlFiles)));

        // those types should be skipped, as used in multiple services with different names
        $ambiguousClassNames = $this->resolveAmbiguousClassTypes($yamlFiles);
        $skipClasses = array_merge($ambiguousClassNames, $skipTypes);

        foreach ($yamlFiles as $yamlFile) {
            if ($yamlFile->getServices() === []) {
                continue;
            }

            foreach ($yamlFile->getServices() as $serviceName => $serviceDefinition) {
                if (! is_array($serviceDefinition)) {
                    continue;
                }

                // skip as on purpose to factory
                if (isset($serviceDefinition['factory'])) {
                    continue;
                }

                if (! $this->argumentDefinitionAnalyzer->hasFullyAutowireabeArguments(
                    $serviceDefinition,
                    $skipClasses
                )) {
                    continue;
                }

                $yamlFile->changedYamlService($serviceName, function (array $serviceDefinition) {
                    unset($serviceDefinition['arguments']);
                    return $serviceDefinition;
                });

                $this->symfonyStyle->note(sprintf('The "%s" service dropped its arguments', $serviceName));
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
