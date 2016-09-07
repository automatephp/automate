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
    const VERBOSITY_NORMAL = 1;
    const VERBOSITY_DEBUG = 10;

    /**
     * Section title.
     *
     * @param string $title
     */
    public function section($title);

    /**
     * Run command.
     *
     * @param string $name
     * @param bool   $verbose
     */
    public function command($name, $verbose = false);

    /**
     * Remote response.
     *
     * @param string $response
     * @param string $server
     * @param bool   $verbose
     */
    public function response($response, $server, $verbose = false);

    /**
     * Remote error.
     *
     * @param string $message
     */
    public function error($message);
}
