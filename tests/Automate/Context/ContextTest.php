<?php

namespace Automate\Tests\Context;

use Automate\Context\ContextInterface;
use Automate\Logger\ConsoleLogger;
use Automate\Model\Server;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use phpseclib3\Net\SSH2;

class ContextTest extends AbstractContextTest
{
    public function testSimpleContext(): void
    {
        $logger = \Mockery::spy(ConsoleLogger::class);
        $ssh = \Mockery::mock(SSH2::class);
        $ssh->expects()->setTimeout(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $server = $this->getServerFromContext($context);

        $this->assertEquals('/home/wwwroot/automate/demo/shared', $context->getSharedPath($server));
    }

    public function testSimpleWithSharedPathContext(): void
    {
        $logger = \Mockery::spy(ConsoleLogger::class);
        $ssh = \Mockery::mock(SSH2::class);
        $ssh->expects()->setTimeout(0);

        $session = new SSHSession($ssh);
        $context = $this->createContextWithServerSharedPath($session, $logger);

        $server = $this->getServerFromContext($context);

        $this->assertEquals('/home/wwwroot/shared', $context->getSharedPath($server));
    }

    /**
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
