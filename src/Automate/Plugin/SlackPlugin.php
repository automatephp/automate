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

use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Event\StartDeployEvent;
use Automate\Model\Project;

class SlackPlugin implements PluginInterface
{
    public function getName()
    {
        return 'slack';
    }


    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::DEPLOY_SUCCESS => 'onSuccess',
            DeployEvents::DEPLOY_FAILED => 'onFailed',
        );
    }
    public function register(Project $project)
    {
    }

    public function getConfigurationSchema()
    {
    }

    public function onSuccess(StartDeployEvent $event)
    {
    }

    public function onFailed(FailedDeployEvent $event)
    {
    }
}
