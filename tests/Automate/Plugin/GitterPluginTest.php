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
use Automate\Plugin\GitterPlugin;
use Automate\Session;
use Automate\Tests\AbstractContextTest;
use Phake;

class GitterPluginTest extends AbstractContextTest
{

    public function testDisablePlugin()
    {
        $gitter = Phake::partialMock(GitterPlugin::class);
        $context = $this->createContext(Phake::mock(Session::class), Phake::mock(LoggerInterface::class));
        $gitter->register($context->getProject());

        $gitter->onInit(new DeployEvent($context));

        Phake::verify($gitter, Phake::times(0))->sendMessage();
    }

    public function testSimpleConfig()
    {
        $gitter = Phake::partialMock(GitterPlugin::class);
        $context = $this->createContext(Phake::mock(Session::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['gitter' => [
            'token' => '123',
            'room'  => '456'
        ]]);

        $gitter->register($context->getProject());

        Phake::when($gitter)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $gitter->onInit(new DeployEvent($context));
        $gitter->onFinish(new DeployEvent($context));
        $gitter->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($gitter, Phake::times(1))->sendMessage(':hourglass: [Automate] [development] Deployment start');
        Phake::verify($gitter, Phake::times(1))->sendMessage(':sunny: [Automate] [development] End of deployment with success');
        Phake::verify($gitter, Phake::times(1))->sendMessage(':exclamation: [Automate] [development] Deployment failed with error');
    }

    public function testMessage()
    {
        $gitter = Phake::partialMock(GitterPlugin::class);
        $context = $this->createContext(Phake::mock(Session::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['gitter' => [
            'token' => '123',
            'room'  => '456',
            'messages' => [
                'start' => '[%platform%] start',
                'success' => '[%platform%] success',
                'failed' => '[%platform%] failed',
            ]
        ]]);

        $gitter->register($context->getProject());

        Phake::when($gitter)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $gitter->onInit(new DeployEvent($context));
        $gitter->onFinish(new DeployEvent($context));
        $gitter->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($gitter, Phake::times(1))->sendMessage('[development] start');
        Phake::verify($gitter, Phake::times(1))->sendMessage('[development] success');
        Phake::verify($gitter, Phake::times(1))->sendMessage('[development] failed');
    }

}
