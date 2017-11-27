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

use Automate\Listener\ClearListener;
use Automate\Listener\LockListener;
use Automate\Model\Project;
use Automate\Plugin\PluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DispatcherFactory
{
    /**
     * @var PluginManager
     */
    private $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * @param Project $project
     * @return EventDispatcher
     */
    public function create(Project $project)
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber(new LockListener());
        $dispatcher->addSubscriber(new ClearListener());

        /** @var PluginInterface $plugin */
        foreach ($this->pluginManager->getPlugins() as $plugin) {
            $plugin->register($project);
            $dispatcher->addSubscriber($plugin);
        }

        return $dispatcher;
    }
}
