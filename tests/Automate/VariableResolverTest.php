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
use Phake;

class VariableResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testAskPassword()
    {
        $io = Phake::mock(SymfonyStyle::class);

        Phake::when($io)->askHidden('Enter a value for password "server_password"')->thenReturn('mypassword');

        $resolver = new VariableResolver($io);

        $platform = new Platform();
        $server = new Server();
        $server->setPassword('%server_password%');
        $platform->addServer($server);

        $resolver->resolve($platform);

        $this->assertEquals('mypassword', $server->getPassword());
    }

    public function testSessionPassword()
    {
        $io = Phake::mock(SymfonyStyle::class);

        $resolver = new VariableResolver($io);

        $platform = new Platform();
        $server = new Server();
        $server->setPassword('%server_password%');
        $platform->addServer($server);

        putenv('AUTOMATE__server_password=sessionPassword');
        $resolver->resolve($platform);

        $this->assertEquals('sessionPassword', $server->getPassword());
    }
}
