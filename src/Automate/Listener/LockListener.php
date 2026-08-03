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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Path;

class LockListener implements EventSubscriberInterface
{
    public const string LOCK_FILE = 'automate.lock';

    /**
     * Names of the servers this deployment actually locked.
     *
     * @var string[]
     */
    private array $lockedServers = [];

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

            // Record the lock right away: a server locked before another one
            // aborts the deployment must still be released, otherwise its lock
            // file stays behind and blocks every later deployment.
            $this->lockedServers[] = (string) $session->getServer()->getName();
        });
    }

    /**
     * Remove lock file.
     */
    public function clearLockFile(DeployEvent $event): void
    {
        if ([] === $this->lockedServers) {
            return;
        }

        $event->getContext()->exec(function (Session $session): void {
            if (!in_array((string) $session->getServer()->getName(), $this->lockedServers, true)) {
                return;
            }

            $session->rm($this->getLockFilePath($session->getServer()));
        });
    }

    /**
     * Get lock file path.
     */
    private function getLockFilePath(Server $server): string
    {
        return Path::join($server->getPath(), self::LOCK_FILE);
    }
}
