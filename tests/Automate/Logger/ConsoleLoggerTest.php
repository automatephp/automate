<?php

namespace Automate\Tests\Logger;

use Automate\Logger\ConsoleLogger;
use Automate\Tests\AbstractMockTestCase;
use Automate\VariableResolver;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLoggerTest extends AbstractMockTestCase
{
    public function testCommandKeepsTheTokenInsteadOfTheValue(): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);
        $resolver = new VariableResolver($io, ['api_token' => 's3cret', 'slug' => 's3cret-slug']);

        (new ConsoleLogger($io, $resolver))->command("curl -H 'Bearer s3cret' /srv/s3cret-slug");

        $this->assertStringContainsString("curl -H 'Bearer %api_token%' /srv/%slug%", $output->fetch());
    }

    public function testCommandIsPrintedAsIsWithoutVariables(): void
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        (new ConsoleLogger($io))->command('ls -la');

        $this->assertStringContainsString('ls -la', $output->fetch());
    }
}
