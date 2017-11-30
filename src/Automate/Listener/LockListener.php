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

class LockListener implements EventSubscriberInterface
{

    private $hasLock = false;
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::INIT =>      'initLockFile',
            DeployEvents::TERMINATE => 'clearLockFile',
            DeployEvents::FAILED =>    'clearLockFile',
        );
    }

    /**
     * Check if a deployment is already in progress
     * and create lock file
     *
     * @param DeployEvent $event
     */
    public function initLockFile(DeployEvent $event)
    {
        $context = $event->getContext();

        foreach($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);
            if($session->exists($this->getLockFilePath($server)) && !$context->isForce()) {
                throw new \RuntimeException('A deployment is already in progress');
            }
        }

        foreach($context->getPlatform()->getServers() as $server) {
            $session = $context->getSession($server);
            $session->touch($this->getLockFilePath($server));
        }

        $this->hasLock = true;
    }

    /**
     * Remove lock file
     *
     * @param DeployEvent $event
     */
    public function clearLockFile(DeployEvent $event)
    {
        $context = $event->getContext();

        if($this->hasLock) {
            foreach($context->getPlatform()->getServers() as $server) {
                $session = $context->getSession($server);
                $session->rm($this->getLockFilePath($server));
            }
        }
    }

    /**
     * Get lock file path.
     *
     * @param Server $server
     *
     * @return string
     */
    public function getLockFilePath(Server $server)
    {
        return $server->getPath().'/automate.lock';
    }
}
