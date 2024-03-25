<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Listener;

use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Model\Server;
use Automate\Workflow\Session;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LockListener implements EventSubscriberInterface
{
    public const string LOCK_FILE = 'automate.lock';

    private bool $hasLock = false;

    public static function getSubscribedEvents(): array
    {
        return [
            DeployEvents::INIT => 'initLockFile',
            DeployEvents::TERMINATE => 'clearLockFile',
            DeployEvents::FAILED => 'clearLockFile',
        ];
    }

    /**
     * Check if a deployment is already in progress
     * and create lock file.
     */
    public function initLockFile(DeployEvent $event): void
    {
        $context = $event->getContext();

        $context->exec(function (Session $session) use ($context): void {
            if ($session->exists($this->getLockFilePath($session->getServer())) && !$context->isForce()) {
                throw new \RuntimeException('A deployment is already in progress');
            }

            $session->touch($this->getLockFilePath($session->getServer()));
        });

        $this->hasLock = true;
    }

    /**
     * Remove lock file.
     */
    public function clearLockFile(DeployEvent $event): void
    {
        $context = $event->getContext();

        if ($this->hasLock) {
            $context->exec(function (Session $session): void {
                $session->rm($this->getLockFilePath($session->getServer()));
            });
        }
    }

    /**
     * Get lock file path.
     */
    private function getLockFilePath(Server $server): string
    {
        return Path::join($server->getPath(), self::LOCK_FILE);
    }
}
