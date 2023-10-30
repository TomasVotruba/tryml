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
use TomasVotruba\Tryml\ServicesResolver;
use TomasVotruba\Tryml\SkippedServicesResolver;
use TomasVotruba\Tryml\YamlTraverser\AddServicesDefaultsYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceNamedServiceToClassesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceMethodCallByTypesYamlTraverser;
use TomasVotruba\Tryml\YamlTraverser\ReplaceServiceNamedArgumentByTypesYamlTraverser;
use Webmozart\Assert\Assert;

final class NamedToTypedServicesCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
        private readonly YamlFinder $yamlFinder,
        private readonly YamlPrinter $yamlPrinter,
        private readonly ServicesResolver $servicesResolver,
        private readonly SkippedServicesResolver $skippedServicesResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('named-to-typed-services');
        $this->setDescription('Move named services to class-typed');

        $this->addArgument('paths', InputArgument::IS_ARRAY, 'Paths to directories with YAML files');

        $this->addOption(
            'skip-type',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Types to skip from replacing'
        );
        $this->addOption(
            'skip-name',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Names to skip from replacing'
        );

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without changing the files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = (array) $input->getArgument('paths');
        Assert::allString($paths);
        Assert::allFileExists($paths);

        $skipTypes = (array) $input->getOption('skip-type');
        $skipNames = (array) $input->getOption('skip-name');
        $isDryRun = (bool) $input->getOption('dry-run');

        $yamlFiles = $this->yamlFinder->findYamlFiles($paths);

        $registeredServiceNames = $this->servicesResolver->resolveRegisteredServiceNames($yamlFiles, $skipTypes);

        $this->symfonyStyle->title('Registered service names');
        $this->symfonyStyle->listing($registeredServiceNames);

        $servicesNamesToSkip = $this->skippedServicesResolver->resolve($yamlFiles, $skipNames, $skipTypes);

        $servicesNamesToReplaceWithClass = $this->servicesResolver->resolveServicesNamesToReplace(
            $registeredServiceNames,
            $servicesNamesToSkip
        );

        $this->symfonyStyle->title('List of services to replace name by type');

        if ($servicesNamesToReplaceWithClass === []) {
            $this->symfonyStyle->warning('None');
            return self::FAILURE;
        }

        // to notice
        $this->symfonyStyle->listing($servicesNamesToReplaceWithClass);
        sleep(2);

        // 1. replace named classes by type if possible
        $replaceNamedServiceToClassesYamlTraverser = new ReplaceNamedServiceToClassesYamlTraverser(
            $servicesNamesToReplaceWithClass
        );
        $replaceNamedServiceToClassesYamlTraverser->traverse($yamlFiles);

        $serviceNameToClassMap = $replaceNamedServiceToClassesYamlTraverser->getServiceNameToClassMap();

        // 2. replace argument service names by classes
        $replaceServiceNamedArgumentByTypesYamlTraverser = new ReplaceServiceNamedArgumentByTypesYamlTraverser(
            $serviceNameToClassMap
        );
        $replaceServiceNamedArgumentByTypesYamlTraverser->traverse($yamlFiles);

        // 3. replace in method calls
        $replaceServiceMethodCallByTypesYamlTraverser = new ReplaceServiceMethodCallByTypesYamlTraverser(
            $serviceNameToClassMap
        );
        $replaceServiceMethodCallByTypesYamlTraverser->traverse($yamlFiles);

        // @todo add 2nd comand to remove explicit arguments if the type is listed in configs as a single class

        $addServicesDefaultsYamlTraverser = new AddServicesDefaultsYamlTraverser();
        $addServicesDefaultsYamlTraverser->traverse($yamlFiles);

        $this->yamlPrinter->print($yamlFiles, $isDryRun);

        return self::SUCCESS;
    }
}
