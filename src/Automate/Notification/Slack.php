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

class Slack
{
    /**
     * @var string
     */
    private $hookUri;

    /**
     * ConsoleLogger constructor.
     *
     * @param SymfonyStyle $io
     * @param int          $verbosity
     */
    public function __construct(SymfonyStyle $io, $verbosity = self::VERBOSITY_NORMAL)
    {
        $this->io = $io;
        $this->verbosity = $verbosity;
    }

    /**
     * {@inheritdoc}
     */
    public function section($title)
    {
        $this->io->block($title, '*', 'fg=white;bg=blue', ' ', true);
    }

    /**
     * {@inheritdoc}
     */
    public function command($name, $verbose = false)
    {
        if ($verbose || $this->verbosity > OutputInterface::VERBOSITY_NORMAL) {
            $this->io->text(sprintf('<info>%s</info>', $name));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function response($response, $server, $verbose = false)
    {
        if ($verbose || $this->verbosity > OutputInterface::VERBOSITY_NORMAL) {
            if (substr_count($response, "\n") > 0) {
                $this->io->text(sprintf('<comment>[%s]</comment>', $server));
                $this->io->text($response);
            } else {
                $this->io->text(sprintf('<comment>[%s]</comment> %s', $server, $response));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->io->error($message);
    }
}
