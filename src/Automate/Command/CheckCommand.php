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

use Automate\Context\SSHContext;
use Automate\Loader;
use Automate\VariableResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('check remote platform.')
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new Loader();
        $project = $loader->load($input->getOption('config'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $io = new SymfonyStyle($input, $output);

        $variableResolver = new VariableResolver($io);
        $variableResolver->resolvePlatform($platform);
        $variableResolver->resolveRepository($project);

        $logger = $this->getLogger($io);

        try {
            $context = new SSHContext($project, $platform, $platform->getDefaultBranch(), $logger);

            $context->connect();
            $logger->section('Check git access');
            $context->run('git ls-remote '.$project->getRepository(), false, null, false);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        $io->success('All is OK');

        return 0;
    }
}
