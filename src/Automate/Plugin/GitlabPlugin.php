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
 * Allow to send a trigger job to Gitlab
 * if the deployment is success or failed
 * only if you're deploying from your remote (not from gitlab)
 *
 * @author Julien Jacottet <jjacottet@gmail.com>
 * @author Romaric Paul <romaric.paul@gmail.com>
 */

class GitlabPlugin implements PluginInterface
{
    /**
     * @var Project
     */
    private $project;

    public function getName()
    {
        return 'gitlab';
    }

    public function getConfigurationSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'uri' => [
                    '_type' => 'text',
                    '_required' => true,
                    '_not_empty' => true,
                ],
                'variables' => [
                    '_type' => 'array',
                    '_children' => [
                        'id_project' => [
                            '_type' => 'number',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
                        'token_trigger' => [
                            '_type' => 'text',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
                        'environment' => [
                            '_type' => 'text',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
                        'ref' => [
                            '_type' => 'text',
                            '_required' => true,
                            '_not_empty' => true,
                        ],
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
        if (isset($project->getPlugins()['gitlab'])){
            $this->project = $project;
        }
    }

    public function onSuccess(DeployEvent $event)
    {
        if (getenv('GITLAB_CI') === false && isset($this->project)) {
            $configuration = $this->project->getPlugins()['gitlab'];
            $gitlabVariables = $configuration["variables"];
            $client = new \GuzzleHttp\Client();

            $msg = str_replace("%branch%", $event->getContext()->getPlatform()->getDefaultBranch(), $gitlabVariables["deploy_successed_msg"]);
            $msg = str_replace("%server%", $event->getContext()->getPlatform()->getName(), $msg);
            $msg = str_replace("%date%", date("d-m-Y H:i:s"), $msg);

            $client->request(
                'POST',
                $configuration['uri'] . "/api/v4/projects/"
                . $gitlabVariables["id_project"]
                . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                . '&token=' . $gitlabVariables["token_trigger"]
                . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                . '&variables[DEPLOY_SUCCESS_MSG]=' . $msg, ['verify' => false]
            );
        }
    }

    public function onFailed(FailedDeployEvent $event)
    {
        if (getenv('GITLAB_CI') === false && isset($this->project)) {
            $configuration = $this->project->getPlugins()['gitlab'];
            $gitlabVariables = $configuration["variables"];
            $client = new \GuzzleHttp\Client();

            $msg = str_replace("%branch%", $event->getContext()->getPlatform()->getDefaultBranch(), $gitlabVariables["deploy_failed_msg"]);
            $msg = str_replace("%server%", $event->getContext()->getPlatform()->getName(), $msg);
            $msg = str_replace("%date%", date("d-m-Y H:i:s"), $msg);

            $client->request(
                'POST',
                $configuration['uri'] . "/api/v4/projects/"
                . $gitlabVariables["id_project"]
                . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                . '&token=' . $gitlabVariables["token_trigger"]
                . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                . '&variables[DEPLOY_FAILED_MSG]=' . $msg . ' ' . $event->getException(), ['verify' => false]
            );
        }
    }
}
