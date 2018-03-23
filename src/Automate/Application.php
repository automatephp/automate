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

use Automate\Command\DeployCommand;
use Automate\Command\LocalDeployCommand;
use Automate\Command\SelfUpdateCommand;
use KevinGH\Amend;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    public function __construct($name = 'Automate', $version = '@git-version@')
    {
        parent::__construct($name, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function getLongVersion()
    {
        if (('@'.'git-version@') !== $this->getVersion()) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
                $this->getName(),
                $this->getVersion(),
                '@git-commit@'
            );
        }

        return '<info>'.$this->getName().'</info> <comment>@dev</comment>';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $commands = parent::getDefaultCommands();

        $commands[] = new DeployCommand();
        $commands[] = new LocalDeployCommand();

        if (('@'.'git-version@') !== $this->getVersion()) {
            $commands[] = new SelfUpdateCommand();
        }

        return $commands;
    }
    
}
