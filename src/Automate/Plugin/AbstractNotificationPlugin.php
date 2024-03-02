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
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

abstract class AbstractNotificationPlugin implements PluginInterface
{
    const MESSAGE_START = ':hourglass: [Automate] [%platform%] Deployment start';
    const MESSAGE_SUCCESS = ':sunny: [Automate] [%platform%] End of deployment with success';
    const MESSAGE_FAILED = ':exclamation: [Automate] [%platform%] Deployment failed with error';

    const INIT = 'onInit';
    const TERMINATE = 'onFinish';
    const FAILED = 'onFailed';

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getName();

    /**
     * Send message to chat service.
     *
     * @param string $message
     * @param string $eventName
     */
    abstract protected function sendMessage($message, $eventName);

    /**
     * {@inheritdoc}
     */
    abstract public function getConfigurationNode();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DeployEvents::INIT => self::INIT,
            DeployEvents::TERMINATE => self::TERMINATE,
            DeployEvents::FAILED => self::FAILED,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(Project $project)
    {
        $this->configuration = $project->getPlugin($this->getName());
    }

    /**
     * Send start deploy message.
     */
    public function onInit(DeployEvent $event)
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('start', self::MESSAGE_START, $event->getContext()), self::INIT);
        }
    }

    /**
     * Send success deploy message.
     */
    public function onFinish(DeployEvent $event)
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('success', self::MESSAGE_SUCCESS, $event->getContext()), self::TERMINATE);
        }
    }

    /**
     * Send failed deploy message.
     */
    public function onFailed(FailedDeployEvent $event)
    {
        if ($this->configuration) {
            $this->sendMessage($this->getMessage('failed', self::MESSAGE_FAILED, $event->getContext(), $event->getException()), self::FAILED);
        }
    }

    protected function getMessagesNode()
    {
        $treeBuilder = new TreeBuilder('messages');

        $node = $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('start')->end()
                ->scalarNode('success')->end()
                ->scalarNode('failed')->end()
            ->end();

        return $node;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return mixed|string
     */
    private function getMessage($name, $default, ContextInterface $context, \Exception $exception = null)
    {
        $message = isset($this->configuration['messages'][$name]) ? $this->configuration['messages'][$name] : $default;

        if (null !== $context->getPlatform()->getName()) {
            $message = str_replace('%platform%', $context->getPlatform()->getName(), $message);
        } else {
            $message = str_replace('[%platform%]', '', $message);
        }

        if ($exception) {
            $message = str_replace('%error%', $exception->getMessage(), $message);
        }

        return $message;
    }
}
