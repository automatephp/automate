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
 * only if you're deploying from your remote (not gitlab)
 */

class GitlabPlugin implements PluginInterface
{
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

    public function onSuccess()
    {
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
        var_dump("failed"); exit;
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
}