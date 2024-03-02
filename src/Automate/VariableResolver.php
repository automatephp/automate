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
    public const string ENV_PREFIX = 'AUTOMATE__';

    public const string VAR_PREFIX = '%';

    public const string VAR_SUFFIX = '%';

    /**
     * VariableResolver constructor.
     */
    public function __construct(
        private readonly SymfonyStyle $io,
    ) {
    }

    /**
     * Resolve platform configuration.
     */
    public function resolvePlatform(Platform $platform): void
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
    public function resolveRepository(Project $project): void
    {
        if (preg_match('/http[s]?:\/\/(?P<user>.*):(?P<variable>%.*%)@(.*)/i', (string) $project->getRepository(), $match)) {
            $password = $this->resolveVariable($match['variable']);
            $repository = str_replace($match['variable'], $password, (string) $project->getRepository());

            $project->setRepository($repository);
        }
    }

    /**
     * Retourn true if value is a variable.
     */
    public function isVariable($value): bool
    {
        $first = substr((string) $value, -1);
        $last = substr((string) $value, 0, 1);

        return self::VAR_PREFIX === $first && self::VAR_SUFFIX === $last;
    }

    /**
     * Resolve a variable.
     */
    public function resolveVariable($value): string
    {
        $name = substr((string) $value, 1, strlen((string) $value) - 2);

        if ($value = getenv(self::ENV_PREFIX.$name)) {
            return $value;
        }

        return $this->io->askHidden(sprintf('Enter a value for password "%s"', $name));
    }
}
