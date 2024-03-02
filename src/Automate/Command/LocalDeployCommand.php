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

use Automate\Context\LocalContext;
use Automate\Loader;
use Automate\Model\Platform;
use Automate\Model\Server;
use Automate\VariableResolver;
use Automate\Workflow\Deployer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LocalDeployCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Start local deployment.')
            ->addArgument('path', InputArgument::REQUIRED, "Project's local path")
            ->addArgument('gitRef', InputArgument::REQUIRED, 'Branch or tag name')
            ->addOption('max-releases', null, InputOption::VALUE_REQUIRED, 'The number of releases to be kept', 3)
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', self::CONFIG_FILE)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to deploy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new Loader();
        $project = $loader->load($input->getOption('config'));

        $platform = $this->createLocalPlatforme($input->getArgument('path'), $input->getOption('max-releases'));

        $io = new SymfonyStyle($input, $output);

        $variableResolver = new VariableResolver($io);
        $variableResolver->resolveRepository($project);

        $logger = $this->getLogger($io);

        $logger->section('Start local deployment');

        $gitRef = $input->getArgument('gitRef');

        $io->table([], [
            ['Repository', $project->getRepository()],
            ['Version', $input->getArgument('gitRef') ?: $platform->getDefaultBranch()],
        ]);

        $context = new LocalContext($project, $platform, $gitRef, $logger, $input->getOption('force'));
        $workflow = new Deployer($context);

        if (!$workflow->deploy()) {
            throw new \RuntimeException('Deployment failed');
        }

        $io->success('All is OK');

        return 0;
    }

    private function createLocalPlatforme($path, $maxReleases)
    {
        $serveur = new Server();
        $serveur
            ->setPath($path)
            ->setName('local');

        $platform = new Platform();
        $platform->setMaxReleases($maxReleases);
        $platform->addServer($serveur);

        return $platform;
    }
}
