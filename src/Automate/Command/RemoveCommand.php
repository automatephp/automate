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
use Automate\Transport\TransportFactory;
use Automate\Workflow\Context;
use Automate\Workflow\Remover;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'remove',
    description: 'Remove the project from the servers of a platform.',
)]
class RemoveCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore an existing lock file')
            ->addOption('keep-shared', null, InputOption::VALUE_NONE, 'Keep the shared folder')
            ->addVariableOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $variableResolver = $this->createVariableResolver($input, $io);

        $loader = new Loader();
        $project = $loader->load($input->getOption('config'), $variableResolver, $input->getArgument('platform'));
        $platform = $project->getPlatform($input->getArgument('platform'));

        $logger = $this->getLogger($io, $variableResolver);
        $logger->section('Start removal');

        $servers = [];
        foreach ($platform->getServers() as $server) {
            $servers[] = sprintf('%s (%s): %s', $server->getName(), $server->getHost(), $server->getPath());
        }

        $rows = [
            ['Platform', $platform->getName()],
            ['Servers', implode("\n", $servers)],
            ['Shared folder', $input->getOption('keep-shared') ? 'kept' : 'removed'],
        ];

        $hookVariables = $variableResolver->getHookVariables();
        if ([] !== $hookVariables) {
            $rows[] = ['Variables', implode(', ', array_keys($hookVariables))];
        }

        $io->table([], $rows);

        if (!$input->getOption('yes') && !$io->confirm('Remove the project from these servers?', false)) {
            $io->warning($input->isInteractive() ? 'Aborted, nothing was removed.' : 'Not confirmed, nothing was removed. Use --yes to remove without confirmation.');

            return Command::FAILURE;
        }

        $transportFactory = new TransportFactory($platform, $variableResolver->getEnvVariables(), $input->getOption('config'));

        $context = new Context($project, $platform, $logger, $transportFactory, new Archiver(), force: $input->getOption('force'), variables: $hookVariables, phase: 'remove');
        $workflow = new Remover($context, $input->getOption('keep-shared'));

        if (!$workflow->remove()) {
            throw new \RuntimeException('Removal failed');
        }

        $io->success('All is OK');

        return Command::SUCCESS;
    }
}
