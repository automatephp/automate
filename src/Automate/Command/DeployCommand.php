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

use Automate\Archiver;
use Automate\Loader;
use Automate\Model\Platform;
use Automate\Ssh\SshFactory;
use Automate\Workflow\Context;
use Automate\Workflow\Deployer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deploy',
    description: 'Start remote deployment.',
)]
class DeployCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('gitRef', InputArgument::OPTIONAL, 'Branch or tag name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to deploy')
            ->addVariableOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $variableResolver = $this->createVariableResolver($input, $io);

        $loader = new Loader();
        $project = $loader->load($input->getOption('config'), $variableResolver, $input->getArgument('platform'));
        $platform = $project->getPlatform($input->getArgument('platform'));

        $logger = $this->getLogger($io);
        $logger->section('Start deployment');

        $gitRef = $input->getArgument('gitRef');

        $rows = [
            ['Repository', $project->getRepository()],
            ['Platform', $platform->getName()],
            ['Servers', $this->getServersList($platform)],
            ['Version', $input->getArgument('gitRef') ?: $platform->getDefaultBranch()],
        ];

        if ([] !== $variableResolver->getHookVariables()) {
            $rows[] = ['Variables', implode("\n", array_map(
                static fn (string $name, string $value): string => sprintf('%s=%s', $name, $value),
                array_keys($variableResolver->getHookVariables()),
                $variableResolver->getHookVariables(),
            ))];
        }

        $io->table([], $rows);

        $sshFactory = new SshFactory($platform, $variableResolver->getEnvVariables(), $input->getOption('config'));

        $context = new Context($project, $platform, $logger, $sshFactory, new Archiver(), $gitRef, $input->getOption('force'));
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
