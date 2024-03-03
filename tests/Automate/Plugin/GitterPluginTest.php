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
use Automate\Tests\AbstractContextTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitterPluginTest extends AbstractContextTestCase
{
    public $client;

    public $gitter;

    public $context;

    public function testDisablePlugin(): void
    {
        $this->initPlugin();

        $this->client->expects('request')->never();

        $this->gitter->onInit(new DeployEvent($this->context));
        $this->gitter->onFinish(new DeployEvent($this->context));
        $this->gitter->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testSimpleConfig(): void
    {
        $this->initPlugin([
            'token' => '123',
            'room' => '456',
        ]);

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => ':hourglass: [Automate] [development] Deployment start',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => ':sunny: [Automate] [development] End of deployment with success',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => ':exclamation: [Automate] [development] Deployment failed with error',
            ],
        ])->once();

        $this->gitter->onInit(new DeployEvent($this->context));
        $this->gitter->onFinish(new DeployEvent($this->context));
        $this->gitter->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testMessage(): void
    {
        $this->initPlugin([
            'token' => '123',
            'room' => '456',
            'messages' => [
                'start' => '[%platform%] start',
                'success' => '[%platform%] success',
                'failed' => '[%platform%] failed',
            ],
        ]);

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => '[development] start',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => '[development] success',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://api.gitter.im/v1/rooms/456/chatMessages', [
            'headers' => [
                'Authorization' => 'Bearer 123',
            ],
            'json' => [
                'text' => '[development] failed',
            ],
        ])->once();

        $this->gitter->onInit(new DeployEvent($this->context));
        $this->gitter->onFinish(new DeployEvent($this->context));
        $this->gitter->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    private function initPlugin(?array $configuration = null): void
    {
        $this->client = \Mockery::mock(HttpClientInterface::class);
        $session = \Mockery::mock(SessionInterface::class);
        $logger = \Mockery::spy(LoggerInterface::class);

        $this->gitter = new GitterPlugin($this->client);
        $this->context = $this->createContext($session, $logger);

        if ($configuration) {
            $this->context->getProject()->setPlugins(['gitter' => $configuration]);
        }

        $this->gitter->register($this->context->getProject());
    }
}
