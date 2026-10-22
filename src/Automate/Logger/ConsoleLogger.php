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

use Automate\Model\Server;
use Automate\VariableResolver;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLogger implements LoggerInterface
{
    public function __construct(
        private readonly SymfonyStyle $io,
        private readonly ?VariableResolver $variableResolver = null,
    ) {
    }

    public function section(string $title): void
    {
        $this->io->block($title, '*', 'fg=white;bg=blue', ' ', true);
    }

    public function command(string $name): void
    {
        $this->io->text(sprintf('<info>%s</info>', $this->mask($name)));
    }

    public function result(string $response, Server $server): void
    {
        if (substr_count($response, "\n") > 0) {
            $this->io->text(sprintf('<comment>[%s]</comment>', $server->getName()));
            $this->io->text($response);
        } else {
            $this->io->text(sprintf('<comment>[%s]</comment> %s', $server->getName(), $response));
        }
    }

    public function info(string $text, ?Server $server = null): void
    {
        if ($server instanceof Server) {
            $this->io->text(sprintf('<comment>[%s]</comment> %s', $server->getName(), $text));
        } else {
            $this->io->text($text);
        }
    }

    public function error(string $message): void
    {
        $this->io->error($message);
    }

    /**
     * Put the token back in place of its value, so that a secret used in a command never reaches the logs.
     */
    private function mask(string $text): string
    {
        $replacements = [];
        foreach ($this->variableResolver?->getMaskedValues() ?? [] as $name => $value) {
            $replacements[$value] = sprintf('%%%s%%', $name);
        }

        // strtr tries the longest value first
        return [] === $replacements ? $text : strtr($text, $replacements);
    }
}
