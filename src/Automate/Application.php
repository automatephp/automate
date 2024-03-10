<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Command\CheckCommand;
use Automate\Command\DeployCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct(string $name = 'Automate', string $version = '@git-version@')
    {
        parent::__construct($name, $version);
    }

    public function getLongVersion(): string
    {
        if ('git-version' !== trim($this->getVersion(), '@')) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
                $this->getName(),
                $this->getVersion(),
                '@git-commit@'
            );
        }

        return '<info>'.$this->getName().'</info> <comment>@dev</comment>';
    }

    protected function getDefaultCommands(): array
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $commands = parent::getDefaultCommands();

        $commands[] = new DeployCommand();
        $commands[] = new CheckCommand();

        return $commands;
    }
}
