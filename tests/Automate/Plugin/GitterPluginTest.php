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
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use GuzzleHttp\ClientInterface;
use Phake;

class GitterPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $gitter = new GitterPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $gitter->register($context->getProject());

        $gitter->onInit(new DeployEvent($context));

        Phake::verify($client, Phake::times(0))->request();
    }

    public function testSimpleConfig()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $gitter = new GitterPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['gitter' => [
            'token' => '123',
            'room'  => '456'
        ]]);

        $gitter->register($context->getProject());

        $gitter->onInit(new DeployEvent($context));
        $gitter->onFinish(new DeployEvent($context));
        $gitter->onFailed(new FailedDeployEvent($context, new \Exception()));

        $uri = 'https://api.gitter.im/v1/rooms/456/chatMessages';

        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => ':hourglass: [Automate] [development] Deployment start'
            ],
            'verify' => false
        ]);
        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => ':sunny: [Automate] [development] End of deployment with success'
            ],
            'verify' => false
        ]);
        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => ':exclamation: [Automate] [development] Deployment failed with error'
            ],
            'verify' => false
        ]);
    }

    public function testMessage()
    {
        $client = Phake::partialMock(ClientInterface::class);
        $gitter = new GitterPlugin($client);

        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));

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

        $gitter->onInit(new DeployEvent($context));
        $gitter->onFinish(new DeployEvent($context));
        $gitter->onFailed(new FailedDeployEvent($context, new \Exception()));

        $uri = 'https://api.gitter.im/v1/rooms/456/chatMessages';

        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => '[development] start'
            ],
            'verify' => false
        ]);
        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => '[development] success'
            ],
            'verify' => false
        ]);
        Phake::verify($client, Phake::times(1))->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer 123')
            ],
            'json' => [
                'text' => '[development] failed'
            ],
            'verify' => false
        ]);
    }
}
