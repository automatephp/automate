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
     */
    public function command($name);

    /**
     * Remote response.
     *
     * @param string $response
     * @param string $server
     */
    public function response($response, $server);

    /**
     * Remote error.
     *
     * @param string $message
     */
    public function error($message);
}
