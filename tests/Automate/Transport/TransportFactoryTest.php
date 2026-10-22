<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Transport;

use Automate\Model\Platform;
use Automate\Model\Server;
use Automate\Model\Transport;
use Automate\Transport\LocalTransport;
use Automate\Transport\SshTransport;
use Automate\Transport\TransportFactory;
use PHPUnit\Framework\TestCase;

class TransportFactoryTest extends TestCase
{
    public function testCreatesAnSshTransportByDefault(): void
    {
        $this->assertInstanceOf(SshTransport::class, $this->createFactory()->create(new Server('web', 'example.com', 'automate')));
    }

    public function testCreatesALocalTransport(): void
    {
        $this->assertInstanceOf(LocalTransport::class, $this->createFactory()->create(new Server(name: 'self', transport: Transport::Local)));
    }

    private function createFactory(): TransportFactory
    {
        return new TransportFactory(new Platform(), [], '.automate.yml');
    }
}
