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
use Automate\Plugin\SentryPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use GuzzleHttp\ClientInterface;
use Phake;

class SentryPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $sentry = new SentryPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $sentry->register($context->getProject());

        $sentry->onInit(new DeployEvent($context));

        Phake::verify($client, Phake::times(0))->request();
    }

    public function testSimpleConfig()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $sentry = new SentryPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $uri = 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/';

        $context->getProject()->setPlugins(['sentry' => [
            'hook_uri' => $uri,
        ]]);

        $sentry->register($context->getProject());

        $sentry->onInit(new DeployEvent($context));
        $sentry->onFinish(new DeployEvent($context));
        $sentry->onFailed(new FailedDeployEvent($context, new \Exception()));

        Phake::verify($client, Phake::times(0))->request();

        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s') . ' ' . ':sunny: [Automate] [development] End of deployment with success'
            ],
            'http_errors' => false
        ]);


        Phake::verify($client, Phake::times(0))->request();
    }

    public function testMessage()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $sentry = new SentryPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $uri = 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/';

        $context->getProject()->setPlugins(['sentry' => [
            'hook_uri' => $uri,
            'messages' => [
                'success' => 'success',
            ]
        ]]);

        $sentry->register($context->getProject());

        $sentry->onFinish(new DeployEvent($context));

        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s') . ' ' . 'success'
            ],
            'http_errors' => false
        ]);
    }
}
