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
use Automate\Event\RemoveEvent;
use Automate\Event\RemoveEvents;
use Automate\Model\Action;
use Automate\Model\Command;
use Symfony\Component\Filesystem\Path;

/**
 * Removal workflow.
 */
readonly class Remover
{
    public function __construct(private Context $context, private bool $keepShared = false)
    {
    }

    /**
     * Remove the project from the servers of the platform.
     */
    public function remove(): bool
    {
        $dispatcher = DispatcherFactory::create();

        try {
            $this->context->connect();

            if (!$this->hasAnythingToRemove()) {
                $this->context->getLogger()->info('Nothing to remove');

                return true;
            }

            $dispatcher->dispatch(new RemoveEvent($this->context), RemoveEvents::INIT);

            // every hook runs before the first deletion: a failing hook leaves all the servers untouched
            $this->runHooks($this->context->getProject()->getOnRemove());
            $this->removeFiles();

            // the lock file is released before the project directory is removed with it
            $dispatcher->dispatch(new RemoveEvent($this->context), RemoveEvents::TERMINATE);
            $this->removeProjectDirectory();

            return true;
        } catch (\Exception $exception) {
            $this->context->getLogger()->error($exception->getMessage());
            try {
                $dispatcher->dispatch(new RemoveEvent($this->context), RemoveEvents::FAILED);
            } catch (\Exception) {
                // ignore exception
            }
        }

        return false;
    }

    private function hasAnythingToRemove(): bool
    {
        $found = false;
        $this->context->exec(static function (Session $session) use (&$found): void {
            $found = $found || $session->exists($session->getServer()->getPath());
        });

        return $found;
    }

    /**
     * @param Action[] $actions
     */
    private function runHooks(array $actions): void
    {
        if ([] === $actions) {
            return;
        }

        $logger = $this->context->getLogger();
        $logger->section('On remove');

        foreach ($actions as $action) {
            if (!$action instanceof Command) {
                continue;
            }

            $logger->command($action->getCmd());

            $this->context->exec(function (Session $session) use ($action, $logger): void {
                $currentPath = $session->getCurrentPath();
                if (!$session->exists($currentPath)) {
                    return;
                }

                $session = $session->withCurrentRelease();
                $result = $session->exec($action->getCmd(), workingDir: $currentPath);
                if ('' !== trim($result)) {
                    $logger->result($result, $session->getServer());
                }
            }, $action->getOnly());
        }
    }

    private function removeFiles(): void
    {
        $this->context->getLogger()->section('Remove files');

        $this->context->exec(function (Session $session): void {
            $logger = $this->context->getLogger();
            $server = $session->getServer();

            // the link first: once the releases are gone it only dangles
            foreach ([$session->getCurrentPath(), $session->getReleasesPath()] as $path) {
                $this->removePath($session, $path);
            }

            $sharedPath = $session->getSharedPath();
            $canonicalPath = Path::canonicalize($server->getPath());
            $canonicalSharedPath = Path::canonicalize($sharedPath);

            if ($this->keepShared) {
                $logger->info(sprintf('Keep %s', $sharedPath), $server);
            } elseif ($canonicalSharedPath === $canonicalPath || !Path::isBasePath($canonicalPath, $canonicalSharedPath)) {
                // it can be used by other projects
                $logger->info(sprintf('%s is outside of the project path, it is kept', $sharedPath), $server);
            } else {
                $this->removePath($session, $sharedPath);
            }
        });
    }

    private function removePath(Session $session, string $path): void
    {
        if ($session->exists($path)) {
            $this->context->getLogger()->info(sprintf('Remove %s', $path), $session->getServer());
            $session->rm($path, true);
        }
    }

    private function removeProjectDirectory(): void
    {
        $this->context->exec(function (Session $session): void {
            $path = $session->getServer()->getPath();
            if (!$session->exists($path)) {
                return;
            }

            $logger = $this->context->getLogger();
            if ('' === trim($session->exec(sprintf('ls -A %s', $path), false))) {
                $logger->info(sprintf('Remove %s', $path), $session->getServer());
                $session->exec(sprintf('rmdir %s', $path), false);
            } else {
                $logger->info(sprintf('%s is not empty, it is kept', $path), $session->getServer());
            }
        });
    }
}
