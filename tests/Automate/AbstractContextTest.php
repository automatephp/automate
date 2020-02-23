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

use Automate\Context\LocalContext;
use Automate\Context\SSHContext;
use Automate\Loader;
use Automate\Logger\LoggerInterface;
use Automate\Session\SessionInterface;
use Automate\SessionFactory;
use PHPUnit\Framework\TestCase;

abstract class AbstractContextTest extends TestCase
{
    protected function createContext(SessionInterface $session, LoggerInterface $logger, $gitRef = null)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = $this->prophesize(SessionFactory::class);
        $sessionFactory->create(current($platform->getServers()))->willReturn($session);

        $context = new SSHContext($project, $platform, $gitRef, $logger, false);
        $context->setSessionFactory($sessionFactory->reveal());
        $context->connect();

        return $context;
    }

    protected function createLocalContext(LoggerInterface $logger, $gitRef = null)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $context = new LocalContext($project, $platform, $gitRef, $logger);
        $context->connect();
        $context->setForce(true);
        $this->assertTrue($context->isForce());

        return $context;
    }

    protected function createContextWithServerSharedPath(SessionInterface $session, LoggerInterface $logger, $gitRef = null)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__ . '/../fixtures/simpleWithSharedPath.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = $this->prophesize(SessionFactory::class);
        $sessionFactory->create(current($platform->getServers()))->willReturn($session);

        $context = new SSHContext($project, $platform, $gitRef, $logger, false);
        $context->setSessionFactory($sessionFactory->reveal());
        $context->connect();

        return $context;
    }
}
