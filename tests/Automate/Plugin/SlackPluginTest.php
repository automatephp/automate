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
use Automate\Plugin\SlackPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use GuzzleHttp\ClientInterface;
use Phake;

class SlackPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $slack = new SlackPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());
        $slack->register($context->getProject());

        $slack->onInit(new DeployEvent($context));
        $slack->onFinish(new DeployEvent($context));
        $slack->onFailed(new FailedDeployEvent($context, new \Exception()));

        $client->request()->shouldNotBeCalled();
    }

    public function testSimpleConfig()
    {
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $slack = new SlackPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $uri = 'https://hooks.slack.com/services/AAAA/BBBB/CCCC';

        $context->getProject()->setPlugins(['slack' => [
            'hook_uri' => $uri,
        ]]);

        $slack->register($context->getProject());

        $slack->onInit(new DeployEvent($context));
        $slack->onFinish(new DeployEvent($context));
        $slack->onFailed(new FailedDeployEvent($context, new \Exception()));

        $client->request('POST', $uri, [
            'json' => [
                'text' => ':hourglass: [Automate] [development] Deployment start'
            ],
            'verify' => false
        ])->shouldBeCalled();

        $client->request('POST', $uri, [
            'json' => [
                'text' => ':sunny: [Automate] [development] End of deployment with success'
            ],
            'verify' => false
        ])->shouldBeCalled();

        $client->request('POST', $uri, [
            'json' => [
                'text' => ':exclamation: [Automate] [development] Deployment failed with error'
            ],
            'verify' => false
        ])->shouldBeCalled();
    }

    public function testMessage()
    {
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $slack = new SlackPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $uri = 'https://hooks.slack.com/services/AAAA/BBBB/CCCC';

        $context->getProject()->setPlugins(['slack' => [
            'hook_uri' => $uri,
            'messages' => [
                'start' => '[%platform%] start',
                'success' => '[%platform%] success',
                'failed' => '[%platform%] failed',
            ]
        ]]);

        $slack->register($context->getProject());

        $slack->onInit(new DeployEvent($context));
        $slack->onFinish(new DeployEvent($context));
        $slack->onFailed(new FailedDeployEvent($context, new \Exception()));

        $client->request('POST', $uri, [
            'json' => [
                'text' => '[development] start'
            ],
            'verify' => false
        ])->shouldBeCalled();

        $client->request('POST', $uri, [
            'json' => [
                'text' => '[development] success'
            ],
            'verify' => false
        ])->shouldBeCalled();

        $client->request('POST', $uri, [
            'json' => [
                'text' => '[development] failed'
            ],
            'verify' => false
        ]);
    }
}
