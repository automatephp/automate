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
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    const VERSION = '@package_version@';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('Automate', self::VERSION);
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new DeployCommand();

        return $defaultCommands;
    }
}
