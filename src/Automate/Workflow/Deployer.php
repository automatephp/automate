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

use Automate\Context;
use Automate\DispatcherFactory;
use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Model\Server;
use Automate\PluginManager;

/**
 * Deployment workflow.
 */
class Deployer
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Deploy project.
     *
     * @return bool
     */
    public function deploy()
    {

        $dispatcher = (new DispatcherFactory(new PluginManager()))->create($this->context->getProject());

        try {

            $this->context->connect();

            $dispatcher->dispatch(DeployEvents::INIT, new DeployEvent($this->context));
            $this->createReleaseDirectory();

            $dispatcher->dispatch(DeployEvents::BUILD, new DeployEvent($this->context));
            $this->deployWithGit();
            $this->runHooks($this->context->getProject()->getPreDeploy(), 'Pre deploy');
            $this->initShared();
            $this->runHooks($this->context->getProject()->getOnDeploy(), 'On deploy');

            $dispatcher->dispatch(DeployEvents::DEPLOY, new DeployEvent($this->context));
            $this->activateSymlink();
            $this->context->setDeployed(true);

            $dispatcher->dispatch(DeployEvents::FINISH, new DeployEvent($this->context));
            $this->runHooks($this->context->getProject()->getPostDeploy(), 'Post deploy');

            $dispatcher->dispatch(DeployEvents::TERMINATE, new DeployEvent($this->context));

            return true;

        } catch (\Exception $e) {
            $this->context->getLogger()->error($e->getMessage());
            try {
                $dispatcher->dispatch(DeployEvents::FAILED, new FailedDeployEvent($this->context, $e));
            } catch (\Exception $e) {
                // ignore exception
            }
        }

        return false;
    }


    /**
     * Prepare release.
     */
    private function deployWithGit()
    {
        $this->context->getLogger()->section('Prepare Release');

        $this->context->run(sprintf(
            'git clone %s -q --recursive -b %s .',
            $this->context->getProject()->getRepository(),
            $this->context->getPlatform()->getDefaultBranch()
        ), true);

        $gitRef = $this->context->getGitRef();

        if ($gitRef) {
            $listTagsCommand = sprintf('git tag --list \'%s\'', $gitRef);
            $this->context->getLogger()->command($listTagsCommand);
            foreach ($this->context->getPlatform()->getServers() as $server) {
                if ($gitRef && $this->context->doRun($server, $listTagsCommand, true)) {
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
     * @param array  $commands
     * @param string $name     section name
     */
    private function runHooks(array $commands, $name)
    {
        if (count($commands)) {
            $this->context->getLogger()->section($name);
            foreach ($commands as $command) {
                if ('' !== $command->getCmd() && '#' !== substr(trim($command->getCmd()), 0, 1)) {
                    $this->context->run($command->getCmd(), true, $command->getOnly());
                }
            }
        }
    }

    /**
     * Setting up shared items.
     */
    private function initShared()
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

    /**
     * @param $path
     * @param Server $server
     * @param bool   $isDirectory
     */
    private function doShared($path, Server $server, $isDirectory)
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
    private function activateSymlink()
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
    private function createReleaseDirectory()
    {
        foreach ($this->context->getPlatform()->getServers() as $server) {
            $this->context->getSession($server)->mkdir($this->context->getReleasePath($server), true);
        }
    }
}
