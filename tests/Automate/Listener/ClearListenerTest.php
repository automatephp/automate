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
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportFactory;
use Automate\Transport\TransportInterface;
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
        $server = new Server()->setName('front-01')->setPath('/srv/2024.01.01-1200.123/app');
        $platform->addServer($server);

        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('find /srv/2024.01.01-1200.123/app/releases -maxdepth 1 -mindepth 1 -type d')->andReturns($releases);

        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->with($server)->andReturns($ssh);

        $context = new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, \Mockery::mock(Archiver::class), releaseId: '2024.03.10-2340.241');
        $context->connect();

        new ClearListener()->clearReleases(new DeployEvent($context));

        $ssh->shouldHaveReceived('exec')->with('rm -R /srv/2024.01.01-1200.123/app/releases/2024.03.08-1000.100')->once();
        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/2024.01.01-1200.123/app/releases/failed']);
        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/2024.01.01-1200.123/app/releases/2024.03.09-1200.123']);
    }

    /**
     * Releases are sorted by name: whatever the sort gets wrong, the live release must survive.
     */
    public function testClearReleasesNeverRemovesTheCurrentRelease(): void
    {
        $releases = implode("\n", [
            '/srv/app/releases/2024.03.09-1200.500',
            '/srv/app/releases/2024.03.09-1200.200',
        ]);

        $platform = new Platform('production', maxReleases: 1);
        $server = new Server()->setName('front-01')->setPath('/srv/app');
        $platform->addServer($server);

        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('find /srv/app/releases -maxdepth 1 -mindepth 1 -type d')->andReturns($releases);
        $ssh->shouldReceive('exec')->with('readlink /srv/app/current || true')->andReturns("/srv/app/releases/2024.03.09-1200.200\n");

        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->with($server)->andReturns($ssh);

        $context = new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, \Mockery::mock(Archiver::class), releaseId: '2024.03.09-1200.200');
        $context->connect();

        new ClearListener()->clearReleases(new DeployEvent($context));

        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/app/releases/2024.03.09-1200.200']);
        $ssh->shouldNotHaveReceived('exec', ['rm -R /srv/app/releases/2024.03.09-1200.500']);
    }
}
