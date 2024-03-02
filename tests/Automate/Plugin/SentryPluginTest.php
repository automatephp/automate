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
use Mockery;

class SentryPluginTest extends AbstractContextTest
{
    public $client;

    public $sentry;

    public $context;

    public function testDisablePlugin()
    {
        $this->initPlugin();

        $this->client->expects('request')->never();

        $this->sentry->onInit(new DeployEvent($this->context));
        $this->sentry->onFinish(new DeployEvent($this->context));
        $this->sentry->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testSimpleConfig()
    {
        $this->initPlugin([
            'hook_uri' => 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/',
        ]);

        $this->client->expects('request')->with('POST', 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s').' '.':sunny: [Automate] [development] End of deployment with success',
            ],
            'http_errors' => false,
            'verify' => false,
        ])->once();

        $this->sentry->onInit(new DeployEvent($this->context));
        $this->sentry->onFinish(new DeployEvent($this->context));
        $this->sentry->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testMessage()
    {
        $this->initPlugin([
            'hook_uri' => 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/',
            'messages' => [
                'success' => 'success',
            ],
        ]);

        $this->client->expects('request')->with('POST', 'https://sentry.io/api/hooks/release/builtin/AAA/BBB/', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'version' => (new \DateTime('now'))->format('Y-m-d H:i:s').' '.'success',
            ],
            'http_errors' => false,
            'verify' => false,
        ])->once();

        $this->sentry->onInit(new DeployEvent($this->context));
        $this->sentry->onFinish(new DeployEvent($this->context));
        $this->sentry->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    private function initPlugin(?array $configuration = null)
    {
        $this->client = Mockery::mock(ClientInterface::class);
        $session = Mockery::mock(SessionInterface::class);
        $logger = Mockery::spy(LoggerInterface::class);

        $this->sentry = new SentryPlugin($this->client);
        $this->context = $this->createContext($session, $logger);

        if ($configuration) {
            $this->context->getProject()->setPlugins(['sentry' => $configuration]);
        }

        $this->sentry->register($this->context->getProject());
    }
}
