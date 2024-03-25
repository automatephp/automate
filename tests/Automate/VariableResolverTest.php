<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests;

use Automate\Model\Platform;
use Automate\Model\Server;
use Automate\VariableResolver;
use Symfony\Component\Console\Style\SymfonyStyle;

class VariableResolverTest extends AbstractMockTestCase
{
    public function testAskPassword(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "server_password"')->andReturns('mypassword');

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        $resolver->process($platform);

        $this->assertEquals('mypassword', $server->getPassword());
    }

    public function testSessionPassword(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        putenv('AUTOMATE__server_password=sessionPassword');
        $resolver->process($platform);

        $this->assertEquals('sessionPassword', $server->getPassword());
    }
}
