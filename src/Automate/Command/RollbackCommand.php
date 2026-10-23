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
use Automate\Workflow\Rollbacker;
use Automate\Workflow\Session;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rollback',
    description: 'Go back to a previous release on the servers of a platform.',
)]
class RollbackCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('release', InputArgument::OPTIONAL, 'Release to go back to (the previous one by default)')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore an existing lock file')
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
        $logger->section('Start rollback');

        $servers = [];
        foreach ($platform->getServers() as $server) {
            $servers[] = sprintf('%s (%s): %s', $server->getName(), $server->getHost(), $server->getPath());
        }

        $release = $input->getArgument('release');
        // the servers check the release exists, but a malformed id is refused before any connection
        if (null !== $release && ($release !== basename((string) $release) || !Session::isReleaseId($release))) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a release id (expected a name like %s)', $release, Context::createReleaseId()));
        }

        $rows = [
            ['Platform', $platform->getName()],
            ['Servers', implode("\n", $servers)],
            ['Release', $release ?? 'the one before the current release'],
        ];

        $hookVariables = $variableResolver->getHookVariables();
        if ([] !== $hookVariables) {
            $rows[] = ['Variables', implode(', ', array_keys($hookVariables))];
        }

        $io->table([], $rows);

        if (!$input->getOption('yes') && !$io->confirm('The current release will be removed, continue?', false)) {
            $io->warning($input->isInteractive() ? 'Aborted, nothing was changed.' : 'Not confirmed, nothing was changed. Use --yes to roll back without confirmation.');

            return Command::FAILURE;
        }

        $sshFactory = new TransportFactory($platform, $variableResolver->getEnvVariables(), $input->getOption('config'));

        $context = new Context($project, $platform, $logger, $sshFactory, new Archiver(), force: $input->getOption('force'), variables: $hookVariables, phase: 'rollback');
        $workflow = new Rollbacker($context, $release);

        if (!$workflow->rollback()) {
            throw new \RuntimeException('Rollback failed');
        }

        $io->success('All is OK');

        return Command::SUCCESS;
    }
}
