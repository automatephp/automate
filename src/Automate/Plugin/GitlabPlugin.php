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

use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Event\SuccessDeployEvent;
use Automate\Model\Project;

/**
 * Allow to send a trigger job to Gitlab
 * if the deployment is success or failed
 * only if you're deploying from your remote (not from gitlab)
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

    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::DEPLOY_SUCCESS => 'onSuccess',
            DeployEvents::DEPLOY_FAILED => 'onFailed',
        );
    }
    public function register(Project $project)
    {
        $this->project = $project;
    }

    public function onSuccess(SuccessDeployEvent $event)
    {
        $configuration = $this->project->getPlugin('gitlab');
        var_dump($configuration);
        var_dump('success'); exit;
        if (getenv('GITLAB_CI') === false ) {
            $gitlabUri = $this->project->getGitlab()["uri"];
            $gitlabVariables = $this->project->getGitlab()["variables"];
            $client = new \GuzzleHttp\Client();

            $client->request(
                'POST',
                $gitlabUri . "/api/v4/projects/"
                . $gitlabVariables["id_project"]
                . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                . '&token=' . $gitlabVariables["token_trigger"]
                . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                . '&variables[DEPLOY_SUCCESS_MSG]=' . $gitlabVariables["deploy_successed_msg"]
                , ['verify' => false]
            );
        }
    }

    public function onFailed(FailedDeployEvent $event)
    {
        $configuration = $this->project->getPlugin('gitlab');
        var_dump($configuration);
        var_dump('failed'); exit;
        if (getenv('GITLAB_CI') === false ) {
            $gitlabUri = $this->project->getGitlab()["uri"];
            $gitlabVariables = $this->project->getGitlab()["variables"];
            $client = new \GuzzleHttp\Client();

            $client->request(
                'POST',
                $gitlabUri . "/api/v4/projects/"
                . $gitlabVariables["id_project"]
                . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                . '&token=' . $gitlabVariables["token_trigger"]
                . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                . '&variables[DEPLOY_FAILED_MSG]=' . $gitlabVariables["deploy_failed_msg"] . $event->getException()
                , ['verify' => false]
            );
        }
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
}