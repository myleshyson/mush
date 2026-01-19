<?php

namespace Myleshyson\Mush;

use Composer\InstalledVersions;
use Myleshyson\Mush\Commands\InstallCommand;
use Myleshyson\Mush\Commands\UpdateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class App extends Application
{
    public static function build(): self
    {
        $app = new self('Mush', self::resolveVersion());

        // Use addCommand if available (Symfony 7.4+), fall back to add for older versions
        // @phpstan-ignore function.alreadyNarrowedType
        if (method_exists($app, 'addCommand')) {
            $app->addCommand(new InstallCommand);
            $app->addCommand(new UpdateCommand);
        } else {
            $app->add(new InstallCommand);
            $app->add(new UpdateCommand);
        }

        // No default command - running `mush` alone shows help

        return $app;
    }

    private static function resolveVersion(): string
    {
        // When installed via Composer, get version from installed packages
        if (class_exists(InstalledVersions::class)) {
            try {
                $version = InstalledVersions::getPrettyVersion('myleshyson/mush');
                if ($version !== null) {
                    return $version;
                }
            } catch (\Exception) {
                // Fall through to default
            }
        }

        return 'dev';
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
