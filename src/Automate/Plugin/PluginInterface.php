<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Plugin;

use Automate\Model\Project;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface PluginInterface extends EventSubscriberInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param Project $project
     */
    public function register(Project $project);

    /**
     * @return array
     */
    public function getConfigurationSchema();
}
