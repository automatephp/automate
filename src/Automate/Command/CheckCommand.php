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
use Automate\Ssh\SshFactory;
use Automate\VariableResolver;
use Automate\Workflow\Context;
use Automate\Workflow\Session;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'check',
    description: 'check remote platform.',
)]
class CheckCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new Loader();
        $project = $loader->load($input->getOption('config'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $io = new SymfonyStyle($input, $output);
        $variableResolver = new VariableResolver($io);
        $variableResolver->process($platform);

        $logger = $this->getLogger($io);
        $sshFactory = new SshFactory($platform, $variableResolver->getEnvVariables(), $input->getOption('config'));

        try {
            $context = new Context($project, $platform, $logger, $sshFactory, new Archiver(), $platform->getDefaultBranch());

            $context->connect();
            $logger->section('Check git access');
            $context->exec(static function (Session $session) use ($project): void {
                $session->exec('git ls-remote '.$project->getRepository(), false);
            });
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('All is OK');

        return Command::SUCCESS;
    }
}
