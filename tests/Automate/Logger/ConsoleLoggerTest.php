<?php

declare(strict_types=1);

namespace Automate\Tests\Logger;

use Automate\Logger\ConsoleLogger;
use Automate\Tests\AbstractMockTestCase;
use Automate\VariableResolver;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLoggerTest extends AbstractMockTestCase
{
    public function testCommandKeepsTheTokenInsteadOfThePassword(): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);
        $resolver = new VariableResolver($io, ['api_token' => 's3cret']);
        $resolver->resolveConfiguration(['platforms' => ['review' => ['servers' => ['vm' => ['password' => '%api_token%']]]]], 'review');

        new ConsoleLogger($io, $resolver)->command("curl -H 'Bearer s3cret'");

        $this->assertStringContainsString("curl -H 'Bearer %api_token%'", $output->fetch());
    }

    public function testCommandKeepsNonSecretVariablesReadable(): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);
        $resolver = new VariableResolver($io, ['slug' => 'crowdsec']);
        $resolver->resolveConfiguration(['path' => '/srv/%slug%'], 'review');

        new ConsoleLogger($io, $resolver)->command('git checkout crowdsec --');

        $this->assertStringContainsString('git checkout crowdsec --', $output->fetch());
    }

    public function testCommandIsPrintedAsIsWithoutVariables(): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        new ConsoleLogger($io)->command('ls -la');

        $this->assertStringContainsString('ls -la', $output->fetch());
    }
}
