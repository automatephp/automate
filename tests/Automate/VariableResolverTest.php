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
use Automate\Model\Server;
use Automate\VariableResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Style\SymfonyStyle;

class VariableResolverTest extends AbstractMockTestCase
{
    public function testAskPassword(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "server_password"')->andReturns('mypassword');

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        $resolver->process($platform);

        $this->assertEquals('mypassword', $server->getPassword());
    }

    public function testMissingPasswordValue(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "missing_password"')->andReturns(null);

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%missing_password%');

        $platform->addServer($server);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve the variable "%missing_password%". Set the "AUTOMATE__missing_password" environment variable or run the command interactively.');

        $resolver->process($platform);
    }

    public function testMissingInputStream(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "missing_password"')->andThrow(new MissingInputException('Aborted.'));

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%missing_password%');

        $platform->addServer($server);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve the variable "%missing_password%". Set the "AUTOMATE__missing_password" environment variable or run the command interactively.');

        $resolver->process($platform);
    }

    public function testSessionPassword(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);

        $resolver = new VariableResolver($io);
        $platform = new Platform();
        $server = new Server();

        $server->setPassword('%server_password%');

        $platform->addServer($server);

        putenv('AUTOMATE__server_password=sessionPassword');
        $resolver->process($platform);

        $this->assertEquals('sessionPassword', $server->getPassword());
    }

    public function testTokenAlone(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class), ['host' => 'a.example.com']);

        $config = $resolver->resolveConfiguration(['repository' => '%host%'], 'review');

        $this->assertSame('a.example.com', $config['repository']);
    }

    public function testTokensInsideStrings(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class), ['slug' => 'feat-x', 'env' => 'review']);

        $config = $resolver->resolveConfiguration(['post_deploy' => [['cmd' => 'deploy %slug% to %env% (%slug%)']]], 'review');

        $this->assertSame('deploy feat-x to review (feat-x)', $config['post_deploy'][0]['cmd']);
    }

    public function testCliVariableTakesPrecedenceOverEnvironment(): void
    {
        putenv('AUTOMATE__prio=fromenv');
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class), ['prio' => 'fromcli']);

        $config = $resolver->resolveConfiguration(['repository' => '%prio%'], 'review');

        putenv('AUTOMATE__prio');
        $this->assertSame('fromcli', $config['repository']);
    }

    public function testEnvironmentVariable(): void
    {
        putenv('AUTOMATE__envonly=fromenv');
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class));

        $config = $resolver->resolveConfiguration(['repository' => 'x-%envonly%'], 'review');

        putenv('AUTOMATE__envonly');
        $this->assertSame('x-fromenv', $config['repository']);
        $this->assertSame(['envonly' => 'fromenv'], $resolver->getHookVariables());
    }

    public function testEscapedPercentAndNonTokensAreLeftAlone(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class));

        $config = $resolver->resolveConfiguration(['post_deploy' => [
            ['cmd' => 'date +%Y'],
            ['cmd' => 'date +%Y-%m-%d'],
            ['cmd' => 'date +%%Y%%m%%d'],
        ]], 'review');

        $this->assertSame('date +%Y', $config['post_deploy'][0]['cmd']);
        $this->assertSame('date +%Y-%m-%d', $config['post_deploy'][1]['cmd']);
        $this->assertSame('date +%Y%m%d', $config['post_deploy'][2]['cmd']);
    }

    public function testDateFormatLookingLikeATokenMustBeEscaped(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve the variable "%m%" used in "post_deploy.0.cmd"');

        $resolver->resolveConfiguration(['post_deploy' => [['cmd' => 'date +%Y%m%d']]], 'review');
    }

    public function testMissingNonPasswordVariableFailsWithoutPrompt(): void
    {
        $io = \Mockery::mock(SymfonyStyle::class);
        $io->shouldNotReceive('askHidden');
        $io->shouldNotReceive('ask');

        $resolver = new VariableResolver($io);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve the variable "%nope%" used in "platforms.review.servers.vm.path"');

        $resolver->resolveConfiguration(['platforms' => ['review' => ['servers' => ['vm' => ['path' => '/srv/%nope%']]]]], 'review');
    }

    public function testPasswordFieldAsksAndIsNotExposedToHooks(): void
    {
        $io = \Mockery::mock(SymfonyStyle::class);
        $io->expects('askHidden')->with('Enter a value for password "pwd"')->andReturns('s3cret');
        $resolver = new VariableResolver($io, ['slug' => 'x']);

        $config = $resolver->resolveConfiguration(['platforms' => ['review' => ['servers' => ['vm' => ['password' => '%pwd%']]]]], 'review');

        $this->assertSame('s3cret', $config['platforms']['review']['servers']['vm']['password']);
        $this->assertSame(['slug' => 'x'], $resolver->getHookVariables());
        $this->assertSame(['AUTOMATE__slug' => 'x', 'AUTOMATE__pwd' => 's3cret'], $resolver->getEnvVariables());
    }

    public function testMissingPasswordInConfigurationKeepsHistoricalMessage(): void
    {
        $io = \Mockery::mock(SymfonyStyle::class);
        $io->expects('askHidden')->andThrow(new MissingInputException('Aborted.'));
        $resolver = new VariableResolver($io);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve the variable "%pwd%". Set the "AUTOMATE__pwd" environment variable or run the command interactively.');

        $resolver->resolveConfiguration(['platforms' => ['review' => ['servers' => ['vm' => ['password' => '%pwd%']]]]], 'review');
    }

    public function testLiteralPasswordIsNotInterpolated(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class));

        $config = $resolver->resolveConfiguration(['platforms' => ['review' => ['servers' => ['vm' => ['password' => 'a%b%c%%']]]]], 'review');

        $this->assertSame('a%b%c%%', $config['platforms']['review']['servers']['vm']['password']);
    }

    public function testOtherPlatformsAreNotResolved(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class));

        $config = $resolver->resolveConfiguration(['platforms' => [
            'review' => ['servers' => ['vm' => ['path' => '/srv']]],
            'prod' => ['servers' => ['vm' => ['path' => '/srv/%nope%']]],
        ]], 'review');

        $this->assertSame('/srv/%nope%', $config['platforms']['prod']['servers']['vm']['path']);
    }

    public function testCliVariablesAreReloadedByTheExecChild(): void
    {
        $resolver = new VariableResolver(\Mockery::mock(SymfonyStyle::class), ['slug' => 'feat-x']);

        $this->assertSame(['AUTOMATE__slug' => 'feat-x'], $resolver->getEnvVariables());
    }

    public function testParseCliVariables(): void
    {
        $this->assertSame(
            ['slug' => 'feat-x', 'url' => 'a=b'],
            VariableResolver::parseCliVariables(['slug=feat-x', 'url=a=b']),
        );
    }

    #[DataProvider('invalidCliVariables')]
    public function testParseInvalidCliVariable(string $option): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariableResolver::parseCliVariables([$option]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCliVariables(): iterable
    {
        yield 'no value' => ['slug'];
        yield 'bad name' => ['Slug=x'];
        yield 'empty name' => ['=x'];
    }
}
