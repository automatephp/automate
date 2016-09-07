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
use Automate\Model\Platform;
use Automate\VariableResolver;
use Automate\Workflow\Deployer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends BaseCommand
{
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

        $logger = $this->getLogger($io);

        $logger->section('Start deployment');

        $io->table(array(), array(
            array('Repository', $project->getRepository()),
            array('Platform', $platform->getName()),
            array('Servers', $this->getServersList($platform)),
            array('Version', $input->getArgument('gitRef') ?: $platform->getDefaultBranch()),
        ));

        $workflow = new Deployer($project, $platform, $logger);

        if ($workflow->deploy($input->getArgument('gitRef'))) {
            $io->success('All is OK');
        }
    }

    private function getServersList(Platform $platform)
    {
        $servers = array();
        foreach ($platform->getServers() as $server) {
            $servers[] = sprintf('%s (%s)', $server->getName(), $server->getHost());
        }

        return implode("\n", $servers);
    }
}
