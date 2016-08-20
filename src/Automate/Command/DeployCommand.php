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

use Automate\Loader;
use Automate\Logger\ConsoleLogger;
use Automate\VariableResolver;
use Automate\Workflow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends Command
{
    const CONFIG_FILE = '.automate.yml';

    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Start deployment.')
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('gitRef', InputArgument::OPTIONAL, 'Branch or tag name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new Loader();
        $project = $loader->load($input->getOption('config'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $io = new SymfonyStyle($input, $output);

        $variableResolver = new VariableResolver($io);
        $variableResolver->resolve($platform);

        $logger = new ConsoleLogger($io);

        $io->title('Start deployment');

        $workflow = new Workflow($project, $platform, $logger);
        $workflow->deploy($input->getArgument('gitRef'));
    }
}
