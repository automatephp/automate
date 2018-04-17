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

abstract class AbstractChatPlugin implements PluginInterface
{
    const MESSAGE_START   = ':hourglass: [Automate] [%platform%] Deployment start';
    const MESSAGE_SUCCESS = ':sunny: [Automate] [%platform%] End of deployment with success';
    const MESSAGE_FAILED  = ':exclamation: [Automate] [%platform%] Deployment failed with error';

    const INIT      = 'onInit';
    const TERMINATE = 'onFinish';
    const FAILED    = 'onFailed';

    /**
     * @var array
     */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    abstract public function getName();

    /**
     * Send message to chat service
     *
     * @param string $message
     * @param string $eventName
     */
    abstract protected function sendMessage($message, $eventName);

    /**
     * {@inheritdoc}
     */
    abstract public function getConfigurationSchema();


    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::INIT => self::INIT,
            DeployEvents::TERMINATE => self::TERMINATE,
            DeployEvents::FAILED => self::FAILED,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(Project $project)
    {
        $this->configuration = $project->getPlugin($this->getName());
    }

    /**
     * Send start deploy message
     *
     * @param DeployEvent $event
     */
    public function onInit(DeployEvent $event)
    {
        if($this->configuration) {
            $this->sendMessage($this->getMessage('start', self::MESSAGE_START, $event->getContext()), self::INIT);
        }
    }

    /**
     * Send success deploy message
     *
     * @param DeployEvent $event
     */
    public function onFinish(DeployEvent $event)
    {
        if($this->configuration) {
            $this->sendMessage($this->getMessage('success', self::MESSAGE_SUCCESS, $event->getContext()), self::TERMINATE);
        }
    }

    /**
     * Send failed deploy message
     *
     * @param FailedDeployEvent $event
     */
    public function onFailed(FailedDeployEvent $event)
    {
        if($this->configuration) {
            $this->sendMessage($this->getMessage('failed', self::MESSAGE_FAILED, $event->getContext(), $event->getException()), self::FAILED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessagesSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'start'   => ['_type' => 'text'],
                'success' => ['_type' => 'text'],
                'failed'  => ['_type' => 'text']
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $default
     * @param ContextInterface $context
     * @param \Exception|null $exception
     * @return mixed|string
     */
    private function getMessage($name, $default, ContextInterface $context, \Exception $exception = null)
    {
        $message = isset($this->configuration['messages'][$name]) ? $this->configuration['messages'][$name] : $default;

        if ($context->getPlatform()->getName() !== null){
            $message = str_replace("%platform%", $context->getPlatform()->getName(), $message);
        }else{
            $message = str_replace("[%platform%]", '', $message);
        }

        if($exception) {
            $message = str_replace("%error%", $exception->getMessage(), $message);
        }

        return $message;
    }
}
