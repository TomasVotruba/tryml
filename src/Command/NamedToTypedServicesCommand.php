<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\FileSystem\YamlFinder;
use Webmozart\Assert\Assert;

final class NamedToTypedServicesCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly YamlFinder $yamlFinder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('named-to-typed-services');
        $this->setDescription('Move named services to class-typed');

        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Paths to directories with YAML files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = (array) $input->getArgument('paths');
        Assert::allString($paths);
        Assert::allFileExists($paths);

        $yamlFiles =  $this->yamlFinder->findYamlFiles($paths);



        return self::SUCCESS;
    }
}
