<?php

declare(strict_types=1);

namespace Automate\Tests\Command;

use Automate\Application;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RollbackCommandTest extends TestCase
{
    public function testRollbackIsRefusedWithoutConfirmation(): void
    {
        $tester = new CommandTester(new Application()->get('rollback'));

        $status = $tester->execute(
            ['platform' => 'review', '--config' => __DIR__.'/../../fixtures/rollback.yml'],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('/srv/review', $tester->getDisplay());
        $this->assertStringContainsString('the one before the current release', $tester->getDisplay());
        $this->assertStringContainsString('Use --yes', $tester->getDisplay());
        $this->assertStringNotContainsString('Variables', $tester->getDisplay());
    }

    #[TestWith(['foo'])]
    #[TestWith(['../2024.03.09-1200.123'])]
    #[TestWith(['2024.03.09-1200.123/'])]
    public function testMalformedReleaseIsRefusedBeforeAnyConnection(string $release): void
    {
        $tester = new CommandTester(new Application()->get('rollback'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('"%s" is not a release id', $release));

        $tester->execute(
            ['platform' => 'review', 'release' => $release, '--config' => __DIR__.'/../../fixtures/rollback.yml', '--yes' => true],
            ['interactive' => false],
        );
    }

    public function testSummaryListsTheVariableNames(): void
    {
        $tester = new CommandTester(new Application()->get('rollback'));

        $tester->execute(
            ['platform' => 'review', '--config' => __DIR__.'/../../fixtures/rollback.yml', '--var' => ['slug=feat-x']],
            ['interactive' => false],
        );

        $this->assertMatchesRegularExpression('/Variables\s+slug/', $tester->getDisplay());
        $this->assertStringNotContainsString('feat-x', $tester->getDisplay());
    }
}
