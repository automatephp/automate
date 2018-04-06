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

class CacheToolPluginTest extends AbstractContextTest
{
    public function testDisablePlugin()
    {
        $cacheTool = Phake::partialMock(CacheToolPlugin::class);
        $context = $this->createContext(Phake::mock(SessionInterface::class), Phake::mock(LoggerInterface::class));
        $cacheTool->register($context->getProject());

        //Phake::verify($cacheTool, Phake::times(0))->sendMessage();
    }
}
