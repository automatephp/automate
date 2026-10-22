<?php

namespace Automate\Tests\Model;

use Automate\Model\Server;
use Automate\Model\Transport;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testLocationIsTheHostOfAnSshServer(): void
    {
        $server = new Server('web', 'example.com', 'automate');

        $this->assertSame('example.com', $server->getLocation());
    }

    public function testLocationOfALocalServer(): void
    {
        $server = new Server(name: 'self', transport: Transport::Local);

        $this->assertSame('local', $server->getLocation());
    }
}
