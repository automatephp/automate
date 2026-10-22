<?php

namespace Automate\Tests\Command;

use Automate\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveCommandTest extends TestCase
{
    public function testRemovalIsRefusedWithoutConfirmation(): void
    {
        $tester = new CommandTester((new Application())->get('remove'));

        $status = $tester->execute(
            ['platform' => 'review', '--config' => __DIR__.'/../../fixtures/onRemove.yml'],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('/srv/review', $tester->getDisplay());
        $this->assertStringContainsString('Use --yes', $tester->getDisplay());
        $this->assertStringNotContainsString('Variables', $tester->getDisplay());
    }

    public function testSummaryListsTheVariableNames(): void
    {
        $tester = new CommandTester((new Application())->get('remove'));

        $tester->execute(
            ['platform' => 'review', '--config' => __DIR__.'/../../fixtures/onRemove.yml', '--var' => ['slug=feat-x']],
            ['interactive' => false],
        );

        $this->assertMatchesRegularExpression('/Variables\s+slug/', $tester->getDisplay());
        $this->assertStringNotContainsString('feat-x', $tester->getDisplay());
    }

    public function testSummaryShowsTheLocalServers(): void
    {
        $tester = new CommandTester((new Application())->get('remove'));

        $status = $tester->execute(
            ['platform' => 'development', '--config' => __DIR__.'/../../fixtures/local.yml'],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('self (local): /home/wwwroot/automate/demo', $tester->getDisplay());
    }
}
