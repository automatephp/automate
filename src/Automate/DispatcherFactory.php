<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Plugin\PluginInterface;
use Automate\Plugin\SlackPlugin;
use phpseclib\Net\SSH2;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DispatcherFactory
{
    /**
     * @param Project $project
     * @return EventDispatcher
     */
    public function create(Project $project)
    {
        $dispatcher = new EventDispatcher();

        /** @var PluginInterface $plugin */
        foreach ($this->getPlugins() as $plugin) {
            $plugin->register($project);
            $dispatcher->addSubscriber($plugin);
        }

        return new $dispatcher();
    }

    private function getPlugins()
    {
        return array(
            new SlackPlugin()
        );
    }
}
