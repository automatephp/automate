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
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\VariableResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    public const string CONFIG_FILE = '.automate.yml';

    protected function getLogger(SymfonyStyle $io): LoggerInterface
    {
        return new ConsoleLogger($io);
    }

    protected function resolveVariables(SymfonyStyle $io, Project $project, Platform $platform): void
    {
        $variableResolver = new VariableResolver($io);
        $variableResolver->resolvePlatform($platform);
        $variableResolver->resolveRepository($project);
    }
}
