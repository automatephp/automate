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
use Automate\Event\FailedDeployEvent;
use Automate\Model\Server;
use Automate\Workflow\Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ClearListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DeployEvents::INIT => 'removeFailedRelease',
            DeployEvents::FAILED => 'moveFailedRelease',
            DeployEvents::TERMINATE => 'clearReleases',
        ];
    }

    /**
     * remove the lasted failed release.
     */
    public function removeFailedRelease(DeployEvent $event): void
    {
        $context = $event->getContext();

        $context->exec(function (Session $session): void {
            if ($session->exists($this->getFailedPath($session->getServer()))) {
                $session->rm($this->getFailedPath($session->getServer()), true);
            }
        });
    }

    /**
     * Move current release to /releases/failed.
     */
    public function moveFailedRelease(FailedDeployEvent $event): void
    {
        $context = $event->getContext();

        // not move if deploy
        if (!$context->isDeployed()) {
            $context->exec(function (Session $session) use ($context): void {
                $release = $session->getReleasePath();
                $failed = $this->getFailedPath($session->getServer());

                $context->getLogger()->info(sprintf('move release to %s', $failed), $session->getServer());

                $session->mv($release, $failed);
            });
        }
    }

    /**
     * Clear olds releases.
     */
    public function clearReleases(DeployEvent $event): void
    {
        $context = $event->getContext();

        $context->getLogger()->section('Clear olds releases');

        $context->exec(static function (Session $session) use ($context): void {
            $releases = $session->listDirectory($session->getReleasesPath());
            $releases = array_map('trim', $releases);
            rsort($releases);
            // ignore others folders
            $releases = array_filter($releases, static fn ($release): int|false => preg_match('/\d{4}\.\d{2}\.\d{2}-\d{4}\./', (string) $release));
            $keep = $context->getPlatform()->getMaxReleases();
            while ($keep > 0) {
                array_shift($releases);
                --$keep;
            }

            foreach ($releases as $release) {
                $context->getLogger()->info('Remove old release : '.$release, $session->getServer());
                $session->rm($release, true);
            }
        });
    }

    private function getFailedPath(Server $server): string
    {
        return $server->getPath().'/releases/failed';
    }
}
