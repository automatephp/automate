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
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\HttpClient\HttpClient;

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
    public const string MESSAGE_SUCCESS = 'Success deployment of "%ref%" on platform "%platform%"';

    public const string MESSAGE_FAILED = 'Failed deployment of "%ref%" on platform "%platform%"';

    private ?Project $project = null;

    
    public static function getSubscribedEvents(): array
    {
        return [
            DeployEvents::TERMINATE => 'onSuccess',
            DeployEvents::FAILED => 'onFailed',
        ];
    }

    
    public function getName(): string
    {
        return 'gitlab';
    }

    
    public function register(Project $project): void
    {
        if (isset($project->getPlugins()['gitlab'])) {
            $this->project = $project;
        }
    }

    /**
     * Create a successed JOB on Gitlab.
     */
    public function onSuccess(DeployEvent $event): void
    {
        if ($this->project instanceof Project) {
            $configuration = $this->project->getPlugin($this->getName());
            $message = $configuration['message']['success'] ?? self::MESSAGE_SUCCESS;
            $this->send($event->getContext(), $message, 'DEPLOY_SUCCESS_MSG');
        }
    }

    /**
     * Create a failed JOB on Gitlab.
     */
    public function onFailed(FailedDeployEvent $event): void
    {
        if ($this->project instanceof Project) {
            $configuration = $this->project->getPlugin($this->getName());
            $message = $configuration['message']['failed'] ?? self::MESSAGE_FAILED;
            $message .= "\n\n\n".$event->getException()->getMessage();
            $this->send($event->getContext(), $message, 'DEPLOY_FAILED_MSG');
        }
    }

    private function send(ContextInterface $context, string $message, string $envName): void
    {
        if (false === getenv('GITLAB_CI')) {
            $configuration = $this->project->getPlugin($this->getName());

            $client = HttpClient::create();

            $ref = $context->getGitRef() ?: $context->getPlatform()->getDefaultBranch();

            $message = str_replace('%ref%', $ref, $message);
            $message = str_replace('%platform%', $context->getPlatform()->getName(), $message);

            $uri = sprintf('%s/api/v4/projects/%s/trigger/pipeline', $configuration['uri'], $configuration['id_project']);

            $client->request('POST', $uri, [
                'query' => [
                    'ref' => $context->getPlatform()->getDefaultBranch(),
                    'token' => $configuration['token_trigger'],
                    'variables[ENVIRONMENT_NAME]' => $context->getPlatform()->getName(),
                    'variables['.$envName.']' => $message,
                ],
            ]);
        }
    }

    
    public function getConfigurationNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('gitlab');

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('uri')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('id_project')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('token_trigger')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('messages')
                    ->children()
                        ->scalarNode('success')->end()
                        ->scalarNode('failed')->end()
                    ->end()
                ->end()
            ->end();
    }
}
