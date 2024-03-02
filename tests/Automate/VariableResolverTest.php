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
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\VariableResolver;
use Mockery;
use Symfony\Component\Console\Style\SymfonyStyle;

class VariableResolverTest extends AbstractMockTestCase
{
    public function testAskPassword()
    {
        $io = Mockery::spy(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "server_password"')->andReturns('mypassword');

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        $resolver->resolvePlatform($platform);

        $this->assertEquals('mypassword', $server->getPassword());
    }

    public function testSessionPassword()
    {
        $io = Mockery::spy(SymfonyStyle::class);

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        putenv('AUTOMATE__server_password=sessionPassword');
        $resolver->resolvePlatform($platform);

        $this->assertEquals('sessionPassword', $server->getPassword());
    }

    public function testRepository()
    {
        $io = Mockery::spy(SymfonyStyle::class);

        $resolver = new VariableResolver($io);
        putenv('AUTOMATE__git_password=sessionPassword');

        $project = new Project();

        $project->setRepository('https://user:%git_password%@exemple.com');

        $resolver->resolveRepository($project);
        $this->assertEquals('https://user:sessionPassword@exemple.com', $project->getRepository());

        $project->setRepository('http://user:%git_password%@exemple.com');
        $resolver->resolveRepository($project);
        $this->assertEquals('http://user:sessionPassword@exemple.com', $project->getRepository());

        $project->setRepository('git@github.com:exemple/exemple.git');
        $resolver->resolveRepository($project);
        $this->assertEquals('git@github.com:exemple/exemple.git', $project->getRepository());
    }
}
