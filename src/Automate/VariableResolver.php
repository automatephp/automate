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
use Automate\Model\Project;
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
     */
    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * Resolve platform configuration.
     */
    public function resolvePlatform(Platform $platform)
    {
        foreach ($platform->getServers() as $server) {
            if ($this->isVariable($server->getPassword())) {
                $password = $this->resolveVariable($server->getPassword());
                $server->setPassword($password);
            }
        }
    }

    /**
     * Resolve repository configuration.
     */
    public function resolveRepository(Project $project)
    {
        if (preg_match('/http[s]?:\/\/(?P<user>.*):(?P<variable>%.*%)@(.*)/i', $project->getRepository(), $match)) {
            $password = $this->resolveVariable($match['variable']);
            $repository = str_replace($match['variable'], $password, $project->getRepository());

            $project->setRepository($repository);
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

        return self::VAR_PREFIX === $first && self::VAR_SUFFIX === $last;
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
