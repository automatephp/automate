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
use Automate\VariableResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    public const string CONFIG_FILE = '.automate.yml';

    protected function addVariableOption(): static
    {
        return $this->addOption('var', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Variable value (name=value)');
    }

    protected function createVariableResolver(InputInterface $input, SymfonyStyle $io): VariableResolver
    {
        return new VariableResolver($io, VariableResolver::parseCliVariables($input->getOption('var')));
    }

    protected function getLogger(SymfonyStyle $io, ?VariableResolver $variableResolver = null): LoggerInterface
    {
        return new ConsoleLogger($io, $variableResolver);
    }
}
