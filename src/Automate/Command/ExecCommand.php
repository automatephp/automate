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
use Automate\Ssh\Ssh;
use Automate\Ssh\SshFactory;
use Automate\Workflow\Context;
use Automate\Workflow\Session;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'exec',
    description: 'Run a command on one server of a platform',
)]
class ExecCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('server', InputArgument::REQUIRED, 'Server name')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Command')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('current', null, InputOption::VALUE_NONE, 'Run the command in the current release, with the AUTOMATE_* variables of the hooks')
            ->addVariableOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $variableResolver = $this->createVariableResolver($input, $io);

        $loader = new Loader();
        $project = $loader->load($input->getOption('config'), $variableResolver, $input->getArgument('platform'));
        $platform = $project->getPlatform($input->getArgument('platform'));
        $server = $platform->getServer($input->getArgument('server'));
        $command = $input->getArgument('cmd');

        if ($input->getOption('current')) {
            $sshFactory = new SshFactory($platform, $variableResolver->getEnvVariables(), $input->getOption('config'));
            $context = new Context($project, $platform, $this->getLogger($io, $variableResolver), $sshFactory, new Archiver(), variables: $variableResolver->getHookVariables(), phase: 'exec');
            $context->connect($server->getName());

            $run = static function () use ($context, $command): string {
                $rs = '';
                $context->exec(static function (Session $session) use ($command, &$rs): void {
                    $session = $session->withCurrentRelease();
                    $rs = $session->exec($command, workingDir: $session->getCurrentPath());
                });

                return $rs;
            };
        } else {
            // internal usage of the asynchronous hooks: a raw command, nothing added
            $ssh = new Ssh($platform, $server, $variableResolver->getEnvVariables(), $input->getOption('config'));
            $ssh->login();

            $run = static fn (): string => $ssh->exec($command);
        }

        try {
            $output->write($run());
        } catch (\Exception $exception) {
            // the parent process reports the failure of an asynchronous hook
            // from the error output: writing there is what makes the reason
            // visible instead of an empty error
            $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            $errorOutput->write($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
