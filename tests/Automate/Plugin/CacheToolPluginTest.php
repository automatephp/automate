<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Plugin;

use Automate\Event\DeployEvent;
use Automate\Logger\ConsoleLogger;
use Automate\Plugin\CacheToolPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTestCase;

class CacheToolPluginTest extends AbstractContextTestCase
{
    public function testSimpleConfig(): void
    {
        $cacheTool = new CacheToolPlugin();
        $session = \Mockery::spy(SessionInterface::class);
        $logger = \Mockery::spy(ConsoleLogger::class);
        $context = $this->createContext($session, $logger);

        $context->getProject()->setPlugins(['cache_tool' => [
            'opcache' => 'true',
            'apcu' => 'true',
            'apc' => 'true',
        ]]);

        $path = $context->getReleasePath(current($context->getProject()->getPlatform('development')->getServers()));
        $session->expects('run')->with('cd '.$path.'; curl -sO '.CacheToolPlugin::PHAR_URL.'cachetool.phar')->once();
        $session->expects('run')->with('cd '.$path.'; php cachetool.phar opcache:reset --fcgi')->once();
        $session->expects('run')->with('cd '.$path.'; php cachetool.phar apcu:cache:clear --fcgi')->once();
        $session->expects('run')->with('cd '.$path.'; php cachetool.phar apc:cache:clear --fcgi')->once();
        $session->expects('run')->with('cd '.$path.'; rm cachetool.phar')->once();

        $cacheTool->register($context->getProject());
        $cacheTool->onTerminate(new DeployEvent($context));
    }

    public function testVersionConfig(): void
    {
        $cacheTool = new CacheToolPlugin();
        $session = \Mockery::spy(SessionInterface::class);
        $logger = \Mockery::spy(ConsoleLogger::class);
        $context = $this->createContext($session, $logger);

        $context->getProject()->setPlugins(['cache_tool' => [
            'version' => '3.2.1',
            'opcache' => 'true',
        ]]);

        $path = $context->getReleasePath(current($context->getProject()->getPlatform('development')->getServers()));
        $session->expects('run')->with('cd '.$path.'; curl -sO '.CacheToolPlugin::PHAR_URL.'cachetool-3.2.1.phar')->once();
        $session->expects('run')->with('cd '.$path.'; php cachetool-3.2.1.phar opcache:reset --fcgi')->once();
        $session->expects('run')->with('cd '.$path.'; rm cachetool-3.2.1.phar')->once();

        $cacheTool->register($context->getProject());
        $cacheTool->onTerminate(new DeployEvent($context));
    }
}
