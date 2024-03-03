<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Logger;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLogger implements LoggerInterface
{
    public function __construct(
        private readonly SymfonyStyle $io,
        private readonly int $verbosity = self::VERBOSITY_NORMAL,
    ) {
    }

    public function section($title): void
    {
        $this->io->block($title, '*', 'fg=white;bg=blue', ' ', true);
    }

    public function command($name, $verbose = false): void
    {
        if ($verbose || $this->verbosity > OutputInterface::VERBOSITY_NORMAL) {
            $this->io->text(sprintf('<info>%s</info>', $name));
        }
    }

    public function response($response, $server, $verbose = false): void
    {
        if ($verbose || $this->verbosity > OutputInterface::VERBOSITY_NORMAL) {
            if (substr_count((string) $response, "\n") > 0) {
                $this->io->text(sprintf('<comment>[%s]</comment>', $server));
                $this->io->text($response);
            } else {
                $this->io->text(sprintf('<comment>[%s]</comment> %s', $server, $response));
            }
        }
    }

    public function error($message): void
    {
        $this->io->error($message);
    }
}
