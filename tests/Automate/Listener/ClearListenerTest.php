<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Listener;

use Automate\Archiver;
use Automate\Event\DeployEvent;
use Automate\Listener\ClearListener;
use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Ssh\Ssh;
use Automate\Ssh\SshFactory;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Context;

class ClearListenerTest extends AbstractMockTestCase
{
    /**
     * The failed release is neither kept as one of the last releases nor removed.
     */
    public function testClearReleasesIgnoresTheFailedRelease(): void
    {
        $releases = implode("\n", [
            '/srv/2024.01.01-1200.123/app/releases/2024.03.08-1000.100',
            '/srv/2024.01.01-1200.123/app/releases/failed',
            '/srv/2024.01.01-1200.123/app/releases/2024.03.09-1200.123',
        ]);

        $platform = new Platform('production', maxReleases: 1);
        $server = (new Server())->setName('front-01')->setPath('/srv/2024.01.01-1200.123/app');
        $platform->addServer($server);

        $ssh = \Mockery::spy(Ssh::class);
        $ssh->shouldReceive('exec')->with('find /srv/2024.01.01-1200.123/app/releases -maxdepth 1 -mindepth 1 -type d')->andReturns($releases);

        $sshFactory = \Mockery::mock(SshFactory::class);
        $sshFactory->shouldReceive('create')->with($server)->andReturns($ssh);

        $context = new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, \Mockery::mock(Archiver::class), releaseId: '2024.03.10-2340.241');
        $context->connect();

        (new ClearListener())->clearReleases(new DeployEvent($context));

        $ssh->shouldHaveReceived('exec')->with('rm -R /srv/2024.01.01-1200.123/app/releases/2024.03.08-1000.100')->once();
        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/2024.01.01-1200.123/app/releases/failed']);
        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/2024.01.01-1200.123/app/releases/2024.03.09-1200.123']);
    }
}
