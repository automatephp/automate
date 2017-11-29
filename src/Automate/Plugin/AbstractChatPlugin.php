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

use Automate\Context;
use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Event\FailedDeployEvent;
use Automate\Model\Project;

abstract class AbstractChatPlugin implements PluginInterface
{
    const MESSAGE_START   = ':hourglass: [Automate] [%platform%] Start deployment';
    const MESSAGE_SUCCESS = ':sunny: [Automate] [%platform%] Finish deployment with success';
    const MESSAGE_FAILED  = ':exclamation: [Automate] [%platform%] Finish deployment with error';

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
     */
    abstract protected function sendMessage($message);

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
            DeployEvents::INIT => 'onInit',
            DeployEvents::TERMINATE => 'onFinish',
            DeployEvents::FAILED => 'onFailed',
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
            $this->sendMessage($this->getMessage('start', self::MESSAGE_START, $event->getContext()));
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
            $this->sendMessage($this->getMessage('success', self::MESSAGE_SUCCESS, $event->getContext()));
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
            $this->sendMessage($this->getMessage('failed', self::MESSAGE_FAILED, $event->getContext(), $event->getException()));
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
                'failed'  => ['_type' => 'text'],
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $defaut
     * @param Context $context
     * @return mixed|string
     */
    private function getMessage($name, $defaut, Context $context, \Exception $exception = null)
    {
        $message = isset($this->configuration['messages'][$name]) ? $this->configuration['messages'][$name] : $defaut;

        $message = str_replace("%platform%", $context->getPlatform()->getName(), $message);

        if($exception) {
            $message = str_replace("%error%", $exception->getMessage(), $message);
        }

        return $message;
    }
}
