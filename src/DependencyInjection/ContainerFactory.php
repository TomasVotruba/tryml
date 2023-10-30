<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\DependencyInjection;

use Illuminate\Container\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\Command\NamedToTypedServicesCommand;

final class ContainerFactory
{
    /**
     * @api
     */
    public function create(): Container
    {
        $container = new Container();

        $container->singleton(
            SymfonyStyle::class,
            static function (): SymfonyStyle {
                // use null output ofr tests to avoid printing
                $consoleOutput = defined('PHPUNIT_COMPOSER_INSTALL') ? new NullOutput() : new ConsoleOutput();
                return new SymfonyStyle(new ArrayInput([]), $consoleOutput);
            }
        );

        $container->singleton(Application::class, function (Container $container): Application {
            $commands = [$container->make(NamedToTypedServicesCommand::class)];

            $application = new Application();
            $application->addCommands($commands);

            $commandNamesToHide = ['list', 'completion', 'help'];
            foreach ($commandNamesToHide as $commandNameToHide) {
                $commandToHide = $application->get($commandNameToHide);
                $commandToHide->setHidden();
            }

            return $application;
        });

        return $container;
    }
}
