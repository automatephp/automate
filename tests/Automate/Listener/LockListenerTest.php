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
use Automate\Listener\LockListener;
use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportFactory;
use Automate\Transport\TransportInterface;
use Automate\Workflow\Context;

class LockListenerTest extends AbstractMockTestCase
{
    private const string LOCK_FILE = '/home/wwwroot/demo/automate.lock';

    public function testLockAndRelease(): void
    {
        [$context, $sshList] = $this->createContext();

        $listener = new LockListener();
        $listener->initLockFile(new DeployEvent($context));
        $listener->clearLockFile(new DeployEvent($context));

        foreach ($sshList as $ssh) {
            $ssh->shouldHaveReceived('exec')->with('touch '.self::LOCK_FILE)->once();
            $ssh->shouldHaveReceived('exec')->with('rm '.self::LOCK_FILE)->once();
        }
    }

    /**
     * A server locked before another one aborts the deployment must still be
     * released, otherwise its lock file blocks every later deployment.
     */
    public function testLockIsReleasedOnAServerWhenAnotherOneIsAlreadyLocked(): void
    {
        [$context, $sshList] = $this->createContext();
        [$first, $second] = $sshList;

        // the second server already holds a lock file
        $second->shouldReceive('exec')
            ->with(sprintf('if test -e "%s" || test -L "%s"; then echo "Y";fi', self::LOCK_FILE, self::LOCK_FILE))
            ->andReturns('Y');

        $listener = new LockListener();

        try {
            $listener->initLockFile(new DeployEvent($context));
            $this->fail('Expected the deployment to be rejected.');
        } catch (\RuntimeException $runtimeException) {
            $this->assertSame('A deployment or a removal is already in progress', $runtimeException->getMessage());
        }

        $listener->clearLockFile(new DeployEvent($context));

        $first->shouldHaveReceived('exec')->with('touch '.self::LOCK_FILE)->once();
        $first->shouldHaveReceived('exec')->with('rm '.self::LOCK_FILE)->once();

        // the lock of the second server belongs to the other deployment
        $second->shouldNotHaveReceived('exec', ['rm '.self::LOCK_FILE]);
    }

    public function testNothingIsReleasedWhenNoLockWasTaken(): void
    {
        [$context, $sshList] = $this->createContext();
        [$first, $second] = $sshList;

        (new LockListener())->clearLockFile(new DeployEvent($context));

        $first->shouldNotHaveReceived('exec', ['rm '.self::LOCK_FILE]);
        $second->shouldNotHaveReceived('exec', ['rm '.self::LOCK_FILE]);
    }

    /**
     * @return array{Context, list<\Mockery\MockInterface>}
     */
    private function createContext(): array
    {
        $platform = new Platform('production');
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshList = [];

        foreach (['front-01', 'front-02'] as $name) {
            $server = (new Server())->setName($name)->setPath('/home/wwwroot/demo');
            $platform->addServer($server);

            $ssh = \Mockery::spy(TransportInterface::class);
            $sshFactory->shouldReceive('create')->with($server)->andReturns($ssh);
            $sshList[] = $ssh;
        }

        $context = new Context(
            new Project(),
            $platform,
            \Mockery::spy(LoggerInterface::class),
            $sshFactory,
            \Mockery::mock(Archiver::class),
            releaseId: '2024.03.10-2340.241'
        );

        $context->connect();

        return [$context, $sshList];
    }
}
