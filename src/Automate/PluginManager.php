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

use Automate\Plugin\PluginInterface;


class PluginManager
{

    /**
     * @var PluginInterface[]
     */
    private $plugins;

    public function __construct()
    {
        // @todo
        $this->plugins = [];
    }

    /**
     * @return PluginInterface[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }
}
