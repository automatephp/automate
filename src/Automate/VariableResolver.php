<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Model\Platform;
use Symfony\Component\Console\Style\SymfonyStyle;

class VariableResolver
{
    const ENV_PREFIX = 'AUTOMATE__';
    const VAR_PREFIX = '%';
    const VAR_SUFFIX = '%';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * VariableResolver constructor.
     *
     * @param SymfonyStyle $io
     */
    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * Resolve platform configuration.
     *
     * @param Platform $platform
     */
    public function resolve(Platform $platform)
    {
        foreach ($platform->getServers() as $server) {
            if ($this->isVariable($server->getPassword())) {
                $password = $this->resolveVariable($server->getPassword());
                $server->setPassword($password);
            }
        }
    }

    /**
     * Retourn true if value is a variable.
     *
     * @param $value
     *
     * @return bool
     */
    public function isVariable($value)
    {
        $first = substr($value, -1);
        $last = substr($value, 0, 1);

        return $first === self::VAR_PREFIX && $last === self::VAR_SUFFIX;
    }

    /**
     * Resolve a variable.
     *
     * @param $value
     *
     * @return string
     */
    public function resolveVariable($value)
    {
        $name = substr($value, 1, strlen($value) - 2);

        if ($value = getenv(self::ENV_PREFIX.$name)) {
            return $value;
        }

        return $this->io->askHidden(sprintf('Enter a value for password "%s"', $name));
    }
}
