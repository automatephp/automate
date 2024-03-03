<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Workflow;

use Automate\Context\ContextInterface;
use Automate\DispatcherFactory;
use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Model\Command;
use Automate\Model\Server;
use Automate\PluginManager;

/**
 * Deployment workflow.
 */
readonly class Deployer
{
    public function __construct(
        private ContextInterface $context,
    ) {
    }

    /**
     * Deploy project.
     */
    public function deploy(): bool
    {
        $dispatcher = (new DispatcherFactory(new PluginManager()))->create($this->context->getProject());

        try {
            $this->context->connect();

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::INIT);
            $this->createReleaseDirectory();

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::BUILD);
            $this->deployWithGit();
            $this->runHooks($this->context->getProject()->getPreDeploy(), 'Pre deploy');
            $this->initShared();
            $this->runHooks($this->context->getProject()->getOnDeploy(), 'On deploy');

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::DEPLOY);
            $this->activateSymlink();
            $this->context->setDeployed(true);

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::FINISH);
            $this->runHooks($this->context->getProject()->getPostDeploy(), 'Post deploy');

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::TERMINATE);

            return true;
        } catch (\Exception $exception) {
            $this->context->getLogger()->error($exception->getMessage());
            try {
                $dispatcher->dispatch(new FailedDeployEvent($this->context, $exception), DeployEvents::FAILED);
            } catch (\Exception) {
                // ignore exception
            }
        }

        return false;
    }

    /**
     * Prepare release.
     */
    private function deployWithGit(): void
    {
        $this->context->getLogger()->section('Prepare Release');

        if ($this->context->getPlatform()->getDefaultBranch()) {
            $clone = sprintf(
                'git clone %s -q --recursive -b %s .',
                $this->context->getProject()->getRepository(),
                $this->context->getPlatform()->getDefaultBranch()
            );
        } else {
            $clone = sprintf(
                'git clone %s -q --recursive .', $this->context->getProject()->getRepository());
        }

        $this->context->run($clone, true);

        $gitRef = $this->context->getGitRef();

        if (null !== $gitRef) {
            $listTagsCommand = sprintf("git tag --list '%s'", $gitRef);
            $this->context->getLogger()->command($listTagsCommand);
            foreach ($this->context->getPlatform()->getServers() as $server) {
                if (null !== $this->context->doRun($server, $listTagsCommand, true)) {
                    // checkout a tag
                    $command = sprintf('git checkout tags/%s', $gitRef);
                } else {
                    // checkout branch or revision
                    $command = sprintf('git checkout %s', $gitRef);
                }

                $this->context->getLogger()->command($command, true);
                $this->context->doRun($server, $command, true, true);
            }
        }
    }

    /**
     * Run hook commands.
     *
     * @param Command[] $commands
     */
    private function runHooks(array $commands, string $name): void
    {
        if ([] !== $commands) {
            $this->context->getLogger()->section($name);
            foreach ($commands as $command) {
                if ('' !== $command->getCmd() && !str_starts_with(trim((string) $command->getCmd()), '#')) {
                    $this->context->run($command->getCmd(), true, $command->getOnly());
                }
            }
        }
    }

    /**
     * Setting up shared items.
     */
    private function initShared(): void
    {
        $folders = $this->context->getProject()->getSharedFolders();
        $files = $this->context->getProject()->getSharedFiles();

        if (count($folders) || count($files)) {
            $this->context->getLogger()->section('Setting up shared items');
            foreach ($this->context->getPlatform()->getServers() as $server) {
                foreach ($folders as $folder) {
                    $this->doShared($folder, $server, true);
                }

                foreach ($files as $file) {
                    $this->doShared($file, $server, false);
                }
            }
        }
    }

    private function doShared(string $path, Server $server, bool $isDirectory): void
    {
        $session = $this->context->getSession($server);

        $path = trim($path);
        $path = ltrim($path, '/');

        $releasePath = $this->context->getReleasePath($server).'/'.$path;
        $sharedPath = $this->context->getSharedPath($server).'/'.$path;

        // For the first deployment : create shared form source
        if (!$session->exists($sharedPath) && $session->exists($releasePath)) {
            $session->mv($releasePath, $sharedPath);
        }

        // Remove from source
        if ($session->exists($releasePath)) {
            $session->rm($releasePath, true);
        }

        // Create path to shared dir in release dir if it does not exist.
        // (symlink will not create the path and will fail otherwise)
        if (!$session->exists(dirname($releasePath))) {
            $session->mkdir(dirname($releasePath), true);
        }

        // ensure shared file or directory exists
        if (!$session->exists($sharedPath)) {
            if ($isDirectory) {
                $session->mkdir($sharedPath, true);
            } else {
                $session->touch($sharedPath);
            }
        }

        // create symlink
        $this->context->getLogger()->response(sprintf('%s --> %s', $releasePath, $sharedPath), $server->getName(), true);
        $session->symlink($sharedPath, $releasePath);
    }

    /**
     * deploy.
     */
    private function activateSymlink(): void
    {
        $this->context->getLogger()->section('Publish new release');

        foreach ($this->context->getPlatform()->getServers() as $server) {
            $currentPath = $this->context->getCurrentPath($server);
            $releasePath = $this->context->getReleasePath($server);

            $this->context->getLogger()->response(sprintf('%s --> %s', $currentPath, $releasePath), $server->getName(), true);
            $this->context->getSession($server)->symlink($releasePath, $currentPath);
        }
    }

    /**
     * Create release directory.
     */
    private function createReleaseDirectory(): void
    {
        foreach ($this->context->getPlatform()->getServers() as $server) {
            $this->context->getSession($server)->mkdir($this->context->getReleasePath($server), true);
        }
    }
}
