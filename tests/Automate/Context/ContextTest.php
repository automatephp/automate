<?php

namespace Automate\Tests\Context;

use Automate\Context\ContextInterface;
use Automate\Logger\ConsoleLogger;
use Automate\Model\Server;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContextTest extends AbstractContextTest
{
    public function testSimpleContext()
    {
        $logger = $this->prophesize(ConsoleLogger::class);
        $ssh = $this->prophesize(SSH2::class);

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $server = $this->getServerFromContext($context);

        $this->assertEquals('/home/wwwroot/automate/demo/shared', $context->getSharedPath($server));
    }

    public function testSimpleWithSharedPathContext()
    {
        $logger = $this->prophesize(ConsoleLogger::class);
        $ssh = $this->prophesize(SSH2::class);

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContextWithServerSharedPath($session, $logger->reveal());

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
