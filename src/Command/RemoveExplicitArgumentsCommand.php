<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\FileSystem\YamlPrinter;
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

        // @todo

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
}
