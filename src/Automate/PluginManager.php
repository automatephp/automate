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

use Automate\Plugin\GitlabPlugin;
use Automate\Plugin\SlackPlugin;

class PluginManager
{

    /**
     * @var PluginInterface[]
     */
    private $plugins;

    public function __construct()
    {
        $this->plugins = array(
            new GitlabPlugin(),
            new SlackPlugin(),
        );
    }

    public function getPlugins()
    {
        return $this->plugins;
    }
}
