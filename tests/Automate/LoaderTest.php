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
use Automate\Model\Command;
use Automate\Model\Project;
use Automate\Model\Server;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testLoader()
    {
        $loder = new Loader();

        $project = $loder->load(__DIR__.'/../fixtures/config.yml');

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('git@github.com:julienj/symfony-demo.git', $project->getRepository());
        $this->assertEquals(array('app/data'), $project->getSharedFolders());
        $this->assertEquals(array('app/config/parameters.yml'), $project->getSharedFiles());

        $this->assertcount(1, $project->getPreDeploy());
        $preDeploy = current($project->getPreDeploy());
        $this->assertInstanceOf(Command::class, $preDeploy);
        $this->assertEquals('php -v', $preDeploy->getCmd());

        foreach ($project->getOnDeploy() as $onDeploy){
            $this->assertInstanceOf(Command::class, $onDeploy);
        }
        
        $this->assertEquals('composer install', $project->getOnDeploy()[0]->getCmd());
        $this->assertEquals('setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var', $project->getOnDeploy()[1]->getCmd());
        $this->assertEquals('setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var', $project->getOnDeploy()[2]->getCmd());

        foreach ($project->getPostDeploy() as $postDeploy){
            $this->assertInstanceOf(Command::class, $postDeploy);
        }

        $this->assertEquals('php bin/console doctrine:cache:clear-metadata', $project->getPostDeploy()[0]->getCmd());
        $this->assertEquals(null , $project->getPostDeploy()[0]->getOnly());

        $this->assertEquals('php bin/console doctrine:schema:update --force', $project->getPostDeploy()[1]->getCmd());
        $this->assertEquals('eddv-exemple-front-01' , $project->getPostDeploy()[1]->getOnly());

        $this->assertEquals('php bin/console doctrine:cache:clear-result', $project->getPostDeploy()[2]->getCmd());
        $this->assertEquals(null , $project->getPostDeploy()[2]->getOnly());

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

    public function testSharedPathLoader()
    {
        $loder = new Loader();

        $project = $loder->load(__DIR__ . '/../fixtures/simpleWithSharedPath.yml');

        $platform = $project->getPlatform('development');

        /** @var Server $server */
        $server = current($platform->getServers());

        $this->assertEquals('/home/wwwroot/shared', $server->getSharedPath());
    }
}
