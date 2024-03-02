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
     * Move current release to /releases/failed.
     */
    public function moveFailedRelease(FailedDeployEvent $event): void
    {
        $context = $event->getContext();

        // not move if deploy
        if (!$context->isDeployed()) {
            foreach ($context->getPlatform()->getServers() as $server) {
                if (null !== $context->getReleasePath($server)) {
                    $session = $context->getSession($server);

                    $release = $context->getReleasePath($server);
                    $failed = $this->getFailedPath($server);

                    $context->getLogger()->response(sprintf('move release to %s', $failed), $server->getName(), true);

                    $session->mv($release, $failed);
                }
            }
        }
    }

    /**
     * remove the lasted failed release.
     */
    public function removeFailedRelease(DeployEvent $event): void
    {
        $context = $event->getContext();

        foreach ($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);
            if ($session->exists($this->getFailedPath($server))) {
                $session->rm($this->getFailedPath($server), true);
            }
        }
    }

    /**
     * Clear olds releases.
     */
    public function clearReleases(DeployEvent $event): void
    {
        $context = $event->getContext();

        $context->getLogger()->section('Clear olds releases');

        foreach ($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);

            $releases = $session->listDirectory($context->getReleasesPath($server));
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
                $context->getLogger()->response('rm -R '.$release, $server->getName(), true);
                $session->rm($release, true);
            }
        }
    }

    private function getFailedPath(Server $server): string
    {
        return $server->getPath().'/releases/failed';
    }
}
