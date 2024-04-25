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

use Automate\DispatcherFactory;
use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Model\Action;
use Automate\Model\Command;
use Automate\Model\Upload;
use Symfony\Component\Filesystem\Path;

/**
 * Deployment workflow.
 */
readonly class Deployer
{
    public function __construct(private Context $context)
    {
    }

    /**
     * Deploy project.
     */
    public function deploy(): bool
    {
        $dispatcher = DispatcherFactory::create();

        try {
            $this->context->connect();

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::INIT);
            $this->createReleaseDirectory();

            $dispatcher->dispatch(new DeployEvent($this->context), DeployEvents::BUILD);

            if ($this->context->getProject()->getRepository()) {
                $this->deployWithGit();
            }

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

        $this->context->exec($clone);

        $gitRef = $this->context->getGitRef();

        if (null !== $gitRef) {
            $listTagsCommand = sprintf("git tag --list '%s'", $gitRef);

            $this->context->exec(function (Session $session) use ($listTagsCommand, $gitRef): void {
                if ('' !== $session->exec($listTagsCommand)) {
                    // checkout a tag
                    $command = sprintf('git checkout tags/%s', $gitRef);
                } else {
                    // checkout branch or revision
                    $command = sprintf('git checkout %s', $gitRef);
                }

                $this->context->getLogger()->command($command);
                $this->context->exec($command);
            });
        }
    }

    /**
     * Run hook.
     *
     * @param Action[] $actions
     */
    private function runHooks(array $actions, string $name): void
    {
        if ([] !== $actions) {
            $this->context->getLogger()->section($name);
            foreach ($actions as $action) {
                if ($action instanceof Command) {
                    $this->context->execAsync($action->getCmd(), $action->getOnly());
                }

                if ($action instanceof Upload) {
                    $this->context->upload($action->getPath(), $action->getExclude(), $action->getOnly());
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
            $this->context->exec(function (Session $session) use ($folders, $files): void {
                foreach ($folders as $folder) {
                    $this->doShared($folder, $session, true);
                }

                foreach ($files as $file) {
                    $this->doShared($file, $session, false);
                }
            });
        }
    }

    private function doShared(string $path, Session $session, bool $isDirectory): void
    {
        $path = trim($path);
        $path = ltrim($path, '/');

        $releasePath = Path::join($session->getReleasePath(), $path);
        $sharedPath = Path::join($session->getSharedPath(), $path);

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
        $session->symlink($sharedPath, $releasePath);
        $this->context->getLogger()->info(sprintf('%s --> %s', $releasePath, $sharedPath), $session->getServer());
    }

    /**
     * deploy.
     */
    private function activateSymlink(): void
    {
        $this->context->getLogger()->section('Publish new release');

        $this->context->exec(function (Session $session): void {
            $currentPath = $session->getCurrentPath();
            $releasePath = $session->getReleasePath();

            $session->symlink($releasePath, $currentPath);
            $this->context->getLogger()->info(sprintf('%s --> %s', $currentPath, $releasePath), $session->getServer());
        });
    }

    /**
     * Create release directory.
     */
    private function createReleaseDirectory(): void
    {
        $this->context->exec(static function (Session $session): void {
            $session->mkdir($session->getReleasePath(), true);
        });
    }
}
