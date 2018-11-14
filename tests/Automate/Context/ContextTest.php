<?php

namespace Automate\Tests\Context;

use Automate\Context\ContextInterface;
use Automate\Logger\ConsoleLogger;
use Automate\Model\Server;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;

class ContextTest extends AbstractContextTest
{
    public function testSimpleContext()
    {
        $logger = Phake::mock(ConsoleLogger::class);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(1);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $server = $this->getServerFromContext($context);

        $this->assertEquals('/home/wwwroot/automate/demo/shared', $context->getSharedPath($server));
    }

    public function testSimpleWithSharedPathContext()
    {
        $logger = Phake::mock(ConsoleLogger::class);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(1);

        $session = new SSHSession($ssh);
        $context = $this->createContextWithServerSharedPath($session, $logger);

        $server = $this->getServerFromContext($context);

        $this->assertEquals('/home/wwwroot/shared', $context->getSharedPath($server));
    }

    /**
     * @param ContextInterface $context
     *
     * @return Server
     */
    private function getServerFromContext(ContextInterface $context)
    {
        $project = $context->getProject();

        $platform = $project->getPlatform('development');

        /** @var Server $server */
        $server = current($platform->getServers());

        return $server;
    }
}
