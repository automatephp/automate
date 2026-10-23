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
use Automate\Model\Action;
use Automate\Model\Command;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Model\Transport;
use Automate\Model\Upload;
use Automate\VariableResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoaderTest extends TestCase
{
    public function testLoader(): void
    {
        $loader = new Loader();

        $project = $loader->load(__DIR__.'/../fixtures/config.yml');

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('git@github.com:julienj/symfony-demo.git', $project->getRepository());
        $this->assertEquals(['app/data'], $project->getSharedFolders());
        $this->assertEquals(['app/config/parameters.yml'], $project->getSharedFiles());

        $this->assertcount(1, $project->getPreDeploy());
        $preDeploy = current($project->getPreDeploy());
        $this->assertInstanceOf(Command::class, $preDeploy);
        $this->assertEquals('php -v', $preDeploy->getCmd());

        foreach ($project->getOnDeploy() as $onDeploy) {
            $this->assertInstanceOf(Command::class, $onDeploy);
        }

        $this->assertEquals('composer install', $project->getOnDeploy()[0]->getCmd());
        $this->assertEquals('setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var', $project->getOnDeploy()[1]->getCmd());
        $this->assertEquals('setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var', $project->getOnDeploy()[2]->getCmd());

        foreach ($project->getPostDeploy() as $postDeploy) {
            $this->assertInstanceOf(Action::class, $postDeploy);
        }

        $this->assertInstanceOf(Command::class, $project->getPostDeploy()[0]);
        $this->assertEquals('php bin/console doctrine:cache:clear-metadata', $project->getPostDeploy()[0]->getCmd());
        $this->assertEquals([], $project->getPostDeploy()[0]->getOnly());

        $this->assertInstanceOf(Command::class, $project->getPostDeploy()[1]);
        $this->assertEquals('php bin/console doctrine:schema:update --force', $project->getPostDeploy()[1]->getCmd());
        $this->assertEquals(['eddv-exemple-front-01'], $project->getPostDeploy()[1]->getOnly());

        $this->assertInstanceOf(Command::class, $project->getPostDeploy()[2]);
        $this->assertEquals('php bin/console doctrine:cache:clear-result', $project->getPostDeploy()[2]->getCmd());
        $this->assertEquals([], $project->getPostDeploy()[2]->getOnly());

        $this->assertInstanceOf(Command::class, $project->getPostDeploy()[3]);
        $this->assertEquals('php bin/console messenger:consume', $project->getPostDeploy()[3]->getCmd());
        $this->assertEquals(['eddv-exemple-front-01', 'dddv-exemple-front-01'], $project->getPostDeploy()[3]->getOnly());

        $this->assertInstanceOf(Upload::class, $project->getPostDeploy()[4]);
        $this->assertEquals('public/build', $project->getPostDeploy()[4]->getPath());
        $this->assertEquals(['vendor'], $project->getPostDeploy()[4]->getExclude());

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

    public function testFirstDeployHookLoader(): void
    {
        $project = new Loader()->load(__DIR__.'/../fixtures/first_deploy.yml');

        $this->assertCount(3, $project->getOnFirstDeploy());
        $this->assertSame('echo first', $project->getOnFirstDeploy()[0]->getCmd());
        $this->assertSame(['web2'], $project->getOnFirstDeploy()[1]->getOnly());
    }

    public function testSharedPathLoader(): void
    {
        $loader = new Loader();

        $project = $loader->load(__DIR__.'/../fixtures/simpleWithSharedPath.yml');

        $platform = $project->getPlatform('development');

        /** @var Server $server */
        $server = current($platform->getServers());

        $this->assertEquals('/home/wwwroot/shared', $server->getSharedPath());
    }

    public function testLoaderResolvesVariablesOfTheRequestedPlatform(): void
    {
        $io = \Mockery::mock(SymfonyStyle::class);
        $resolver = new VariableResolver($io, ['slug' => 'feat-x', 'review_password' => 'secret', 'dotted_password' => 'dotted']);

        $project = new Loader()->load(__DIR__.'/../fixtures/variables.yml', $resolver, 'review');

        $server = $project->getPlatform('review')->getServer('vm');
        $this->assertEquals('/srv/review/feat-x', $server->getPath());
        $this->assertEquals('secret', $server->getPassword());
        $this->assertEquals('dotted', $project->getPlatform('review')->getServer('web.example.com')->getPassword());
        $this->assertEquals('bash deploy.sh feat-x', $project->getPostDeploy()[0]->getCmd());
        $this->assertEquals('date +%m%d', $project->getPostDeploy()[1]->getCmd());
        $this->assertEquals('%prod_password%', $project->getPlatform('production')->getServer('prod')->getPassword());
        $this->assertEquals('/srv/%unused_slug%', $project->getPlatform('production')->getServer('prod')->getPath());
    }

    public function testLoaderReadsTheOnRemoveHook(): void
    {
        $project = new Loader()->load(__DIR__.'/../fixtures/onRemove.yml');

        $this->assertCount(2, $project->getOnRemove());
        $this->assertInstanceOf(Command::class, $project->getOnRemove()[0]);
        $this->assertEquals('docker compose down', $project->getOnRemove()[0]->getCmd());
        $this->assertEquals(['vm'], $project->getOnRemove()[1]->getOnly());
    }

    public function testLoaderRejectsAnUploadInTheOnRemoveHook(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('The "upload" action is not supported in "on_remove"');

        new Loader()->load(__DIR__.'/../fixtures/onRemoveUpload.yml');
    }

    public function testLoadLocalServer(): void
    {
        $project = new Loader()->load(__DIR__.'/../fixtures/local.yml');

        $server = $project->getPlatform('development')->getServer('self');
        $this->assertSame(Transport::Local, $server->getTransport());
        $this->assertTrue($server->isLocal());
        $this->assertNull($server->getHost());
        $this->assertNull($server->getUser());
    }

    public function testServerUsesSshByDefault(): void
    {
        $project = new Loader()->load(__DIR__.'/../fixtures/simple.yml');

        $server = $project->getPlatform('development')->getServer('dddv-exemple-front-01');
        $this->assertSame(Transport::Ssh, $server->getTransport());
        $this->assertFalse($server->isLocal());
    }

    public function testSshServerRequiresHostAndUser(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('automate.platforms.development.servers.web');
        $this->expectExceptionMessageIsOrContains('The "host" option is required with the "ssh" transport.');

        new Loader()->load(__DIR__.'/../fixtures/ssh_without_host.yml');
    }

    public function testLocalServerRejectsSshOptions(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('automate.platforms.development.servers.self');
        $this->expectExceptionMessageIsOrContains('The "user" option is not supported with the "local" transport');

        new Loader()->load(__DIR__.'/../fixtures/local_with_ssh_options.yml');
    }

    public function testUnknownTransportIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('ftp');

        new Loader()->load(__DIR__.'/../fixtures/unknown_transport.yml');
    }

    public function testLoaderReadsThePostRollbackHook(): void
    {
        $project = new Loader()->load(__DIR__.'/../fixtures/rollback.yml');

        $this->assertCount(2, $project->getPostRollback());
        $this->assertEquals('docker compose down', $project->getPostRollback()[0]->getCmd());
        $this->assertEquals(['vm'], $project->getPostRollback()[1]->getOnly());
        $this->assertSame([], $project->getOnRemove());
    }

    public function testLoaderRejectsAnUploadInThePostRollbackHook(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageIsOrContains('The "upload" action is not supported in "post_rollback"');

        new Loader()->load(__DIR__.'/../fixtures/postRollbackUpload.yml');
    }
}
