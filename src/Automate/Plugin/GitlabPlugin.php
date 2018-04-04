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

use Automate\Context\ContextInterface;
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
 *
 * Usage :
 *
 * Add this job in the .gitlab-ci.yml
 *
 * deploy_from_remote:
 *   stage: deploy
 *   environment:
 *     name: "$ENVIRONMENT_NAME"
 *   script:
 *     - if [ -n "${DEPLOY_FAILED_MSG}" ]; then echo "$DEPLOY_FAILED_MSG";exit 1; fi
 *     - if [ -n "${DEPLOY_SUCCESS_MSG}" ]; then echo "$DEPLOY_SUCCESS_MSG";exit 0; fi
 *
 */
class GitlabPlugin implements PluginInterface
{
    const MESSAGE_SUCCESS = 'Success deployment of "%ref%" on platform "%platform%"';
    const MESSAGE_FAILED  = 'Failed deployment of "%ref%" on platform "%platform%"';

    /**
     * @var Project
     */
    private $project;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::TERMINATE => 'onSuccess',
            DeployEvents::FAILED => 'onFailed',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gitlab';
    }

    /**
     * {@inheritdoc}
     */
    public function register(Project $project)
    {
        if (isset($project->getPlugins()['gitlab'])){
            $this->project = $project;
        }
    }

    /**
     * Create a successed JOB on Gitlab
     *
     * @param DeployEvent $event
     */
    public function onSuccess(DeployEvent $event)
    {
        if($this->project) {
            $configuration = $this->project->getPlugin($this->getName());
            $message = isset($configuration['message']['success']) ? $configuration['message']['success'] : self::MESSAGE_SUCCESS;
            $this->send($event->getContext(), $message, 'DEPLOY_SUCCESS_MSG');
        }
    }

    /**
     * Create a failed JOB on Gitlab
     *
     * @param FailedDeployEvent $event
     */
    public function onFailed(FailedDeployEvent $event)
    {
        if($this->project) {
            $configuration = $this->project->getPlugin($this->getName());
            $message = isset($configuration['message']['failed']) ? $configuration['message']['failed'] : self::MESSAGE_FAILED;
            $message .= "\n\n\n" . $event->getException()->getMessage();
            $this->send($event->getContext(), $message, 'DEPLOY_FAILED_MSG');
        }
    }

    /**
     * Create the job
     *
     * @param ContextInterface $context
     * @param string $message
     * @param string $envName
     */
    private function send(ContextInterface $context, $message, $envName)
    {
        if (getenv('GITLAB_CI') === false) {
            $configuration = $this->project->getPlugin($this->getName());

            $client = new \GuzzleHttp\Client();

            $ref = $context->getGitRef() ?: $context->getPlatform()->getDefaultBranch();

            $message = str_replace("%ref%", $ref, $message);
            $message = str_replace("%platform%", $context->getPlatform()->getName(), $message);

            $uri = sprintf('%s/api/v4/projects/%s/trigger/pipeline', $configuration['uri'], $configuration['id_project']);

            $client->request('POST', $uri, [
                'query'=> [
                    'ref' => $context->getPlatform()->getDefaultBranch(),
                    'token' => $configuration['token_trigger'],
                    'variables[ENVIRONMENT_NAME]' => $context->getPlatform()->getName(),
                    'variables['.$envName.']' => $message
                ],
                'verify' => false
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
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
                'messages' => [
                    '_type' => 'array',
                    '_children' => [
                        'success' => ['_type' => 'text'],
                        'failed' => ['_type' => 'text'],
                    ],
                ]
            ]
        ];
    }
}
