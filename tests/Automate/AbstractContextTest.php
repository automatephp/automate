<?php
/*
 * This file is part of the ShopEngine package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests;


use Automate\Context\SSHContext;
use Automate\Loader;
use Automate\Logger\LoggerInterface;
use Automate\Session\SessionInterface;
use Automate\SessionFactory;
use Phake;

abstract class AbstractContextTest extends \PHPUnit_Framework_TestCase
{
    protected function createContext(SessionInterface $session, LoggerInterface $logger, $gitRef = null)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = Phake::mock(SessionFactory::class);
        Phake::when($sessionFactory)->create(current($platform->getServers()))->thenReturn($session);

        $context = new SSHContext($project, $platform, $gitRef, $logger, false, $sessionFactory);
        $context->connect();

        return $context;
    }
}