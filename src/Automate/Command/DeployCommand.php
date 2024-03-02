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
use Automate\Model\Platform;
use Automate\VariableResolver;
use Automate\Workflow\Deployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends BaseCommand
{
    protected static $defaultDescription = 'Start remote deployment.';

    protected function configure(): void
    {
        $this
            ->setName('deploy')
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('gitRef', InputArgument::OPTIONAL, 'Branch or tag name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to deploy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new Loader();
        $project = $loader->load($input->getOption('config'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $io = new SymfonyStyle($input, $output);

        $variableResolver = new VariableResolver($io);
        $variableResolver->resolvePlatform($platform);
        $variableResolver->resolveRepository($project);

        $logger = $this->getLogger($io);

        $logger->section('Start deployment');

        $gitRef = $input->getArgument('gitRef');

        $io->table([], [
            ['Repository', $project->getRepository()],
            ['Platform', $platform->getName()],
            ['Servers', $this->getServersList($platform)],
            ['Version', $input->getArgument('gitRef') ?: $platform->getDefaultBranch()],
        ]);

        $context = new SSHContext($project, $platform, $logger, $gitRef, $input->getOption('force'));
        $workflow = new Deployer($context);

        if (!$workflow->deploy()) {
            throw new \RuntimeException('Deployment failed');
        }

        $io->success('All is OK');

        return Command::SUCCESS;
    }

    private function getServersList(Platform $platform): string
    {
        $servers = [];
        foreach ($platform->getServers() as $server) {
            $servers[] = sprintf('%s (%s)', $server->getName(), $server->getHost());
        }

        return implode("\n", $servers);
    }
}
