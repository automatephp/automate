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

use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLogger implements  LoggerInterface
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @param SymfonyStyle $io
     */
    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function section($title)
    {
        $this->io->title($title);
    }

    /**
     * {@inheritdoc}
     */
    public function command($name)
    {
        $this->io->comment($name);
    }

    /**
     * {@inheritdoc}
     */
    public function response($response, $server)
    {
        $this->io->text(sprintf('[%s] %s', $server, $response));
    }

    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->io->error($message);
    }
}