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

readonly class DispatcherFactory
{
    public function __construct(
        private PluginManager $pluginManager,
    ) {
    }

    public function create(Project $project): EventDispatcher
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
