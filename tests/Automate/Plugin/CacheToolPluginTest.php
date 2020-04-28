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
use Automate\Event\FailedDeployEvent;
use Automate\Logger\LoggerInterface;
use Automate\Plugin\CacheToolPlugin;
use Automate\Plugin\GitterPlugin;
use Automate\Session\SessionInterface;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;

class CacheToolPluginTest extends AbstractContextTest
{
    public function testSimpleConfig()
    {
        $cacheTool = new CacheToolPlugin();
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $context->getProject()->setPlugins(['cache_tool' => [
            'opcache' => 'true',
            'apcu' => 'true',
            'apc' => 'true'
        ]]);

        $cacheTool->register($context->getProject());
        $cacheTool->onTerminate(new DeployEvent($context));

        $path = $context->getReleasePath(current($context->getProject()->getPlatform('development')->getServers()));

        $session->run('cd '.$path.'; curl -sO ' . CacheToolPlugin::PHAR_URL . 'cachetool.phar')->shouldBeCalled();
        $session->run('cd '.$path.'; php cachetool.phar opcache:reset --fcgi')->shouldBeCalled();
        $session->run('cd '.$path.'; php cachetool.phar apcu:cache:clear --fcgi')->shouldBeCalled();
        $session->run('cd '.$path.'; php cachetool.phar apc:cache:clear --fcgi')->shouldBeCalled();
        $session->run('cd '.$path.'; rm cachetool.phar')->shouldBeCalled();
    }

    public function testVersionConfig()
    {
        $cacheTool = new CacheToolPlugin();
        $session = $this->prophesize(SessionInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $context = $this->createContext($session->reveal(), $logger->reveal());

        $context->getProject()->setPlugins(['cache_tool' => [
            'version' => '3.2.1',
            'opcache' => 'true',
        ]]);

        $cacheTool->register($context->getProject());
        $cacheTool->onTerminate(new DeployEvent($context));

        $path = $context->getReleasePath(current($context->getProject()->getPlatform('development')->getServers()));

        $session->run('cd '.$path.'; curl -sO ' . CacheToolPlugin::PHAR_URL . 'cachetool-3.2.1.phar' )->shouldBeCalled();
        $session->run('cd '.$path.'; php cachetool-3.2.1.phar opcache:reset --fcgi')->shouldBeCalled();
        $session->run('cd '.$path.'; rm cachetool-3.2.1.phar')->shouldBeCalled();
    }
}
