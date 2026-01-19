<?php

namespace Myleshyson\Fusion;

use Myleshyson\Fusion\Commands\InstallCommand;
use Myleshyson\Fusion\Commands\UpdateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class App extends Application
{
    public static function build(): self
    {
        $app = new self('Fusion', '1.0.0');
        $app->addCommand(new InstallCommand);
        $app->addCommand(new UpdateCommand);

        // No default command - running `fusion` alone shows help

        return $app;
    }

    public function getDefinition(): InputDefinition
    {
        $definition = parent::getDefinition();

        $definition->addOption(new InputOption(
            name: 'working-dir',
            shortcut: 'w',
            mode: InputOption::VALUE_REQUIRED,
            description: 'The working directory to run the command in.',
        ));

        return $definition;
    }
}
