<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Command;

use Automate\Logger\ConsoleLogger;
use Automate\Logger\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    public const string CONFIG_FILE = '.automate.yml';

    protected function getLogger(SymfonyStyle $io): LoggerInterface
    {
        $verbosity = $io->getVerbosity() > OutputInterface::VERBOSITY_NORMAL
            ? LoggerInterface::VERBOSITY_DEBUG
            : LoggerInterface::VERBOSITY_NORMAL;

        return new ConsoleLogger($io, $verbosity);
    }
}
