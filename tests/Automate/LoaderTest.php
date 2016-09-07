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

use Automate\Loader;
use Automate\Model\Project;
use Automate\Model\Server;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $loder = new Loader();

        $project = $loder->load(__DIR__.'/../fixtures/config.yml');

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('git@github.com:julienj/symfony-demo.git', $project->getRepository());
        $this->assertEquals(array('app/data'), $project->getSharedFolders());
        $this->assertEquals(array('app/config/parameters.yml'), $project->getSharedFiles());
        $this->assertEquals(array('php -v'), $project->getPreDeploy());
        $this->assertEquals(array(
            'composer install',
            'setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var',
            'setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var',
        ), $project->getOnDeploy());
        $this->assertEquals(array('php bin/console doctrine:schema:update --force'), $project->getPostDeploy());

        $this->assertCount(2, $project->getPlatforms());

        $platform = $project->getPlatform('development');
        $this->assertEquals('development', $platform->getName());
        $this->assertEquals('master', $platform->getDefaultBranch());
        $this->assertEquals(3, $platform->getMaxReleases());
        $this->assertCount(1, $platform->getServers());

        /** @var Server $server */
        $server = current($platform->getServers());

        $this->assertEquals('dddv-exemple-front-01', $server->getName());
        $this->assertEquals('192.168.1.18', $server->getHost());
        $this->assertEquals('root', $server->getUser());
        $this->assertEquals('%dev_password%', $server->getPassword());
        $this->assertEquals('/home/wwwroot/automate/demo', $server->getPath());
    }
}
