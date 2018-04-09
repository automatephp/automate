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
    public function testDisablePlugin()
    {
        $cacheTool = Phake::partialMock(CacheToolPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $cacheTool->register($context->getProject());

        Phake::verify($cacheTool, Phake::times(0))->onTerminate();
    }

    public function testSimpleConfig()
    {
        $cacheTool = new CacheToolPlugin();
        $session  = Phake::mock(SessionInterface::class);
        $context = $this->createContext($session, Phake::mock(LoggerInterface::class));

        $context->getProject()->setPlugins(['cache_tool' => [
            'opcache' => 'true',
            'apcu' => 'true',
            'apc' => 'true'
        ]]);

        $cacheTool->register($context->getProject());
        $cacheTool->onTerminate(new DeployEvent($context));

        $path = $context->getReleasePath(current($context->getProject()->getPlatform('development')->getServers()));

        Phake::inOrder(
            Phake::verify($session, Phake::times(1))->run('cd '.$path.'; curl -sO ' . CacheToolPlugin::PHAR_URL),
            Phake::verify($session, Phake::times(1))->run('cd '.$path.'; php cachetool.phar opcache:reset --fcgi'),
            Phake::verify($session, Phake::times(1))->run('cd '.$path.'; php cachetool.phar apcu:cache:clear --fcgi'),
            Phake::verify($session, Phake::times(1))->run('cd '.$path.'; php cachetool.phar apc:cache:clear --fcgi'),
            Phake::verify($session, Phake::times(1))->run('cd '.$path.'; rm cachetool.phar')
        );
    }
}
