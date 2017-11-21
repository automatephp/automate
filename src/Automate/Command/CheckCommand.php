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
use Automate\VariableResolver;
use Automate\Workflow\Inspector;
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
            ->setDescription('check platform.')
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pluginManger = new PluginManager();

        $loader = new Loader($pluginManger);
        $project = $loader->load($input->getOption('config'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $io = new SymfonyStyle($input, $output);

        $variableResolver = new VariableResolver($io);
        $variableResolver->resolvePlatform($platform);
        $variableResolver->resolveRepository($project);

        $logger = $this->getLogger($io);

        $logger->section('Ckeck '.$platform->getName());

        $io->table(array(), array(
            array('Repository', $project->getRepository()),
            array('Platform', $platform->getName()),
        ));

        $inspector = new Inspector($project, $platform, $logger, $pluginManger);

        if ($inspector->inspect()) {
            $io->success('All is OK');
        }
    }
}
