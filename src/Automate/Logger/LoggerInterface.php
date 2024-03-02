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

interface LoggerInterface
{
    public const int VERBOSITY_NORMAL = 1;

    public const int VERBOSITY_DEBUG = 10;

    /**
     * Section title.
     */
    public function section(string $title);

    /**
     * Run command.
     */
    public function command(string $name, bool $verbose = false);

    /**
     * Remote response.
     */
    public function response(string $response, string $server, bool $verbose = false);

    /**
     * Remote error.
     */
    public function error(string $message);
}
