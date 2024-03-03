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
use Automate\Tests\AbstractContextTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackPluginTest extends AbstractContextTestCase
{
    public $client;

    public $slack;

    public $context;

    public function testDisablePlugin(): void
    {
        $this->initPlugin();
        $this->client->expects('request')->never();

        $this->slack->onInit(new DeployEvent($this->context));
        $this->slack->onFinish(new DeployEvent($this->context));
        $this->slack->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testSimpleConfig(): void
    {
        $this->initPlugin([
            'hook_uri' => 'https://hooks.slack.com/services/AAAA/BBBB/CCCC',
        ]);

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => ':hourglass: [Automate] [development] Deployment start',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => ':sunny: [Automate] [development] End of deployment with success',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => ':exclamation: [Automate] [development] Deployment failed with error',
            ],
        ])->once();

        $this->slack->onInit(new DeployEvent($this->context));
        $this->slack->onFinish(new DeployEvent($this->context));
        $this->slack->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    public function testMessage(): void
    {
        $this->initPlugin([
            'hook_uri' => 'https://hooks.slack.com/services/AAAA/BBBB/CCCC',
            'messages' => [
                'start' => '[%platform%] start',
                'success' => '[%platform%] success',
                'failed' => '[%platform%] failed',
            ],
        ]);

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => '[development] start',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => '[development] success',
            ],
        ])->once();

        $this->client->expects('request')->with('POST', 'https://hooks.slack.com/services/AAAA/BBBB/CCCC', [
            'json' => [
                'text' => '[development] failed',
            ],
        ])->once();

        $this->slack->onInit(new DeployEvent($this->context));
        $this->slack->onFinish(new DeployEvent($this->context));
        $this->slack->onFailed(new FailedDeployEvent($this->context, new \Exception()));
    }

    private function initPlugin(?array $configuration = null): void
    {
        $this->client = \Mockery::mock(HttpClientInterface::class);
        $session = \Mockery::mock(SessionInterface::class);
        $logger = \Mockery::spy(LoggerInterface::class);

        $this->slack = new SlackPlugin($this->client);
        $this->context = $this->createContext($session, $logger);

        if ($configuration) {
            $this->context->getProject()->setPlugins(['slack' => $configuration]);
        }

        $this->slack->register($this->context->getProject());
    }
}
