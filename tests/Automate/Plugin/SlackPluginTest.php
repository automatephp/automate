<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Plugin;

use Automate\Event\DeployEvent;
use Automate\Event\FailedDeployEvent;
use Automate\Logger\LoggerInterface;
use Automate\Plugin\AbstractChatPlugin;
use Automate\Plugin\SlackPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use Phake;

class SlackPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $slack = Phake::partialMock(SlackPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $slack->register($context->getProject());

        $slack->onInit(new DeployEvent($context));

        Phake::verify($slack, Phake::times(0))->sendMessage();
    }

    public function testSimpleConfig()
    {
        $slack = Phake::partialMock(SlackPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['slack' => [
            'uri' => 'https://hooks.slack.com/services/AAAA/BBBB/CCCC',
        ]]);

        $slack->register($context->getProject());

        Phake::when($slack)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $slack->onInit(new DeployEvent($context));
        $slack->onFinish(new DeployEvent($context));
        $slack->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($slack, Phake::times(1))->sendMessage(':hourglass: [Automate] [development] Deployment start', AbstractChatPlugin::INIT);
        Phake::verify($slack, Phake::times(1))->sendMessage(':sunny: [Automate] [development] End of deployment with success', AbstractChatPlugin::TERMINATE);
        Phake::verify($slack, Phake::times(1))->sendMessage(':exclamation: [Automate] [development] Deployment failed with error', AbstractChatPlugin::FAILED);
    }

    public function testMessage()
    {
        $slack = Phake::partialMock(SlackPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['slack' => [
            'uri' => 'https://hooks.slack.com/services/AAAA/BBBB/CCCC',
            'messages' => [
                'start' => '[%platform%] start',
                'success' => '[%platform%] success',
                'failed' => '[%platform%] failed',
            ]
        ]]);

        $slack->register($context->getProject());

        Phake::when($slack)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $slack->onInit(new DeployEvent($context));
        $slack->onFinish(new DeployEvent($context));
        $slack->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($slack, Phake::times(1))->sendMessage('[development] start', AbstractChatPlugin::INIT);
        Phake::verify($slack, Phake::times(1))->sendMessage('[development] success', AbstractChatPlugin::TERMINATE);
        Phake::verify($slack, Phake::times(1))->sendMessage('[development] failed', AbstractChatPlugin::FAILED);
    }
}
