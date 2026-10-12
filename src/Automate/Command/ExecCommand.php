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
use Automate\Ssh\Ssh;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'exec',
    description: 'Exec ssh command',
)]
class ExecCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setHidden()
            ->addArgument('platform', InputArgument::REQUIRED, 'Platform name')
            ->addArgument('server', InputArgument::REQUIRED, 'server name')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Command')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
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

        $ssh = new Ssh($platform, $server, $variableResolver->getEnvVariables(), $input->getOption('config'));
        $ssh->login();

        try {
            $rs = $ssh->exec($command);
            $output->write($rs);
        } catch (\Exception $exception) {
            $output->write($exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
