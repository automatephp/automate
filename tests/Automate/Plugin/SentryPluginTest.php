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
use Automate\Plugin\SentryPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use Phake;

class SentryPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $sentry = Phake::partialMock(SentryPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $sentry->register($context->getProject());

        $sentry->onFinish(new DeployEvent($context));

        Phake::verify($sentry, Phake::times(0))->sendMessage();
    }

    public function testSimpleConfig()
    {
        $sentry = Phake::partialMock(SentryPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['sentry' => [
            'uri' => 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/',
        ]]);

        $sentry->register($context->getProject());

        Phake::when($sentry)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $sentry->onInit(new DeployEvent($context));
        $sentry->onFinish(new DeployEvent($context));
        $sentry->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($sentry, Phake::times(0))->sendMessage();
        Phake::verify($sentry, Phake::times(1))->sendMessage(':sunny: [Automate] [development] End of deployment with success', AbstractChatPlugin::TERMINATE);
        Phake::verify($sentry, Phake::times(0))->sendMessage();
    }

    public function testMessage()
    {
        $sentry = Phake::partialMock(SentryPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['sentry' => [
            'uri' => 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/',
            'messages' => [
                'success' => 'success',
            ]
        ]]);

        $sentry->register($context->getProject());

        Phake::when($sentry)->sendMessage(Phake::anyParameters())->thenReturn(true);

        $sentry->onFinish(new DeployEvent($context));

        Phake::verify($sentry, Phake::times(1))->sendMessage('success', AbstractChatPlugin::TERMINATE);
    }
}
