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
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractNotificationPlugin implements PluginInterface
{
    public const string MESSAGE_START = ':hourglass: [Automate] [%platform%] Deployment start';

    public const string MESSAGE_SUCCESS = ':sunny: [Automate] [%platform%] End of deployment with success';

    public const string MESSAGE_FAILED = ':exclamation: [Automate] [%platform%] Deployment failed with error';

    public const string INIT = 'onInit';

    public const string TERMINATE = 'onFinish';

    public const string FAILED = 'onFailed';

    protected ?array $configuration = null;

    protected HttpClientInterface $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: HttpClient::create();
    }

    
    abstract public function getName(): string;

    abstract protected function sendMessage(string $message, string $eventName);

    
    abstract public function getConfigurationNode(): \Symfony\Component\Config\Definition\Builder\NodeDefinition;

    
    public static function getSubscribedEvents(): array
    {
        return [
            DeployEvents::INIT => self::INIT,
            DeployEvents::TERMINATE => self::TERMINATE,
            DeployEvents::FAILED => self::FAILED,
        ];
    }

    
    public function register(Project $project): void
    {
        $this->configuration = $project->getPlugin($this->getName());
    }

    public function onInit(DeployEvent $event): void
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('start', self::MESSAGE_START, $event->getContext()), self::INIT);
        }
    }

    public function onFinish(DeployEvent $event): void
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('success', self::MESSAGE_SUCCESS, $event->getContext()), self::TERMINATE);
        }
    }

    public function onFailed(FailedDeployEvent $event): void
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('failed', self::MESSAGE_FAILED, $event->getContext(), $event->getException()), self::FAILED);
        }
    }

    protected function getMessagesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('messages');

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('start')->end()
                ->scalarNode('success')->end()
                ->scalarNode('failed')->end()
            ->end();
    }

    private function getMessage(string $name, string $default, ContextInterface $context, \Exception $exception = null): string
    {
        $message = $this->configuration['messages'][$name] ?? $default;

        if (null !== $context->getPlatform()->getName()) {
            $message = str_replace('%platform%', $context->getPlatform()->getName(), (string) $message);
        } else {
            $message = str_replace('[%platform%]', '', (string) $message);
        }

        if ($exception instanceof \Exception) {
            $message = str_replace('%error%', $exception->getMessage(), $message);
        }

        return $message;
    }
}
