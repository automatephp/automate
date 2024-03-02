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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LockListener implements EventSubscriberInterface
{
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

        foreach ($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);
            if ($session->exists($this->getLockFilePath($server)) && !$context->isForce()) {
                throw new \RuntimeException('A deployment is already in progress');
            }
        }

        foreach ($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);
            $session->touch($this->getLockFilePath($server));
        }

        $this->hasLock = true;
    }

    /**
     * Remove lock file.
     */
    public function clearLockFile(DeployEvent $event): void
    {
        $context = $event->getContext();

        if ($this->hasLock) {
            foreach ($context->getPlatform()->getServers() as $server) {
                $session = $context->getSession($server);
                $session->rm($this->getLockFilePath($server));
            }
        }
    }

    /**
     * Get lock file path.
     */
    public function getLockFilePath(Server $server): string
    {
        return $server->getPath().'/automate.lock';
    }
}
