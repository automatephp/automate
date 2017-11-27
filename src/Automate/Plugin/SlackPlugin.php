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
use Automate\Model\Project;

/**
 * Allow to send a notification job to a channel slack
 * if the deployment is success or failed
 *
 * @author Julien Jacottet <jjacottet@gmail.com>
 * @author Romaric Paul <romaric.paul@gmail.com>
 */

class SlackPlugin implements PluginInterface
{
    /**
     * @var Project
     */
    private $project;

    public function getName()
    {
        return 'slack';
    }

    public function getConfigurationSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'hook_uri' => [
                    '_type' => 'text',
                    '_required' => true,
                    '_not_empty' => true,
                ],
                'variables' => [
                    '_type' => 'array',
                    '_children' => [
                        'deploy_successed_msg' => [
                            '_type' => 'text',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
                        'deploy_failed_msg' => [
                            '_type' => 'text',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
                    ],
                ]
            ]
        ];
    }

    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::TERMINATE => 'onSuccess',
            DeployEvents::FAILED => 'onFailed',
        );
    }
    public function register(Project $project)
    {
        if (isset($project->getPlugins()['slack'])){
            $this->project = $project;
        }
    }

    public function onSuccess(DeployEvent $event)
    {
        if (isset($this->project)) {
            $configuration = $this->project->getPlugins()['slack'];
            $slackVariables = $configuration["variables"];
            $client = new \GuzzleHttp\Client();

            $msg = str_replace("%branch%", $event->getContext()->getPlatform()->getDefaultBranch(), $slackVariables["deploy_successed_msg"]);
            $msg = str_replace("%server%", $event->getContext()->getPlatform()->getName(), $msg);
            $msg = str_replace("%date%", date("d-m-Y H:i:s"), $msg);

            $client->request(
                'POST',
                $configuration['hook_uri'],
                [
                    'json' => [
                        'text' => $msg
                    ],
                    'verify' => false
                ]
            );
        }
    }

    public function onFailed(FailedDeployEvent $event)
    {
        if (isset($this->project)) {
            $configuration = $this->project->getPlugins()['slack'];
            $slackVariables = $configuration["variables"];
            $client = new \GuzzleHttp\Client();

            $msg = str_replace("%branch%", $event->getContext()->getPlatform()->getDefaultBranch(), $slackVariables["deploy_failed_msg"]);
            $msg = str_replace("%server%", $event->getContext()->getPlatform()->getName(), $msg);
            $msg = str_replace("%date%", date("d-m-Y H:i:s"), $msg);

            $client->request(
                'POST',
                $configuration['hook_uri'],
                [
                    'json' => [
                        'text' => $msg
                    ],
                    'verify' => false
                ]
            );
        }
    }
}
