<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\Console\InputHelper;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use TomasVotruba\Tryml\FileSystem\YamlPrinter;
use TomasVotruba\Tryml\SkippedServicesResolver;
use Webmozart\Assert\Assert;

final class TrimArgumentsCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly YamlFinder $yamlFinder,
        private readonly YamlPrinter $yamlPrinter,
        private readonly SkippedServicesResolver $skippedServicesResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('trim-arguments');
        $this->setDescription('Replaced typed arguments and use explicit named ones for rest');

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
        $paths = (array) $input->getArgument('paths');
        Assert::allString($paths);
        Assert::allFileExists($paths);

        $skipTypes = InputHelper::resolveSkippedTypes($input);

        $isDryRun = (bool) $input->getOption('dry-run');

        $yamlFiles = $this->yamlFinder->findYamlFiles($paths);

        $servicesNamesToSkip = $this->skippedServicesResolver->resolve($yamlFiles, [], $skipTypes);

        $this->symfonyStyle->title('Replacing explicit arguments with autowired and named ones');

        // $this->namedServicesYamlProcessor->processYamlFiles($servicesNamesToReplaceWithClass, $yamlFiles);

        // $this->yamlPrinter->print($yamlFiles, $isDryRun);

        return self::SUCCESS;
    }
}