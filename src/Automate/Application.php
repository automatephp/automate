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
        $commands[] = new CheckCommand();

        if (('@'.'git-version@') !== $this->getVersion()) {
            $updateCommand = new Amend\Command('update');
            $updateCommand->setManifestUri('@manifest_url@');
            $commands[] = $updateCommand;
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        if (('@'.'git-version@') !== $this->getVersion()) {
            $helperSet->set(new Amend\Helper());
        }

        return $helperSet;
    }
}
