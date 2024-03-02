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
    private $plugins = [];

    public function __construct()
    {
        foreach (new \DirectoryIterator(__DIR__.'/Plugin/') as $file) {
            if ($file->isFile()) {
                $class = 'Automate\\Plugin\\'.substr($file->getFilename(), 0, -4);
                $ref = new \ReflectionClass($class);
                if (!$ref->isAbstract() && $ref->implementsInterface(PluginInterface::class)) {
                    $this->plugins[] = new $class();
                }
            }
        }
    }

    /**
     * @return PluginInterface[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }
}
