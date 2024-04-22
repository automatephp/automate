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

interface LoggerInterface
{
    public function section(string $title): void;

    public function command(string $name): void;

    public function result(string $response, Server $server): void;

    public function info(string $text, ?Server $server = null): void;

    public function error(string $message): void;
}
