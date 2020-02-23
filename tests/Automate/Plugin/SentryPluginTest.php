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
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $sentry = new SentryPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());
        $sentry->register($context->getProject());

        $sentry->onInit(new DeployEvent($context));
        $sentry->onFinish(new DeployEvent($context));
        $sentry->onFailed(new FailedDeployEvent($context, new \Exception()));

        $client->request()->shouldNotBeCalled();
    }

    public function testSimpleConfig()
    {
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $sentry = new SentryPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $uri = 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/';

        $context->getProject()->setPlugins(['sentry' => [
            'hook_uri' => $uri,
        ]]);

        $sentry->register($context->getProject());

        $sentry->onInit(new DeployEvent($context));
        $sentry->onFinish(new DeployEvent($context));
        $sentry->onFailed(new FailedDeployEvent($context, new \Exception()));

        $client->request('POST', $uri, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s') . ' ' . ':sunny: [Automate] [development] End of deployment with success'
            ],
            'http_errors' => false,
            'verify' => false
        ])->shouldBeCalled();

    }

    public function testMessage()
    {
        $client = $this->prophesize(ClientInterface::class);
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $sentry = new SentryPlugin($client->reveal());

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $uri = 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/';

        $context->getProject()->setPlugins(['sentry' => [
            'hook_uri' => $uri,
            'messages' => [
                'success' => 'success',
            ]
        ]]);

        $sentry->register($context->getProject());

        $sentry->onFinish(new DeployEvent($context));

        $client->request('POST', $uri, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s') . ' ' . 'success'
            ],
            'http_errors' => false,
            'verify' => false
        ])->shouldBeCalled();
    }
}
