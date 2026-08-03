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
use Symfony\Component\Console\Exception\MissingInputException;
use Symfony\Component\Console\Style\SymfonyStyle;

class VariableResolver
{
    private const string ENV_PREFIX = 'AUTOMATE__';

    private const string VAR_PREFIX = '%';

    private const string VAR_SUFFIX = '%';

    /** @var array<string, string> */
    private array $variables = [];

    public function __construct(
        private readonly SymfonyStyle $io,
    ) {
    }

    public function process(Platform $platform): void
    {
        foreach ($platform->getServers() as $server) {
            if ($server->getPassword() && $this->isVariable($server->getPassword())) {
                $password = $this->resolveVariable($server->getPassword());
                $this->variables[$server->getPassword()] = $password;
                $server->setPassword($password);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function getEnvVariables(): array
    {
        $envs = [];
        foreach ($this->variables as $name => $variable) {
            $name = trim($name, '%');
            $envs[self::ENV_PREFIX.$name] = $variable;
        }

        return $envs;
    }

    /**
     * Return true if value is a variable.
     */
    private function isVariable(string $value): bool
    {
        $first = substr($value, -1);
        $last = substr($value, 0, 1);

        return self::VAR_PREFIX === $first && self::VAR_SUFFIX === $last;
    }

    /**
     * Resolve a variable.
     */
    private function resolveVariable(string $value): string
    {
        $name = substr($value, 1, strlen($value) - 2);

        $env = getenv(self::ENV_PREFIX.$name);
        if (false !== $env && '' !== $env) {
            return $env;
        }

        try {
            $answer = $this->io->askHidden(sprintf('Enter a value for password "%s"', $name));
        } catch (MissingInputException) {
            // no input stream available (non-interactive terminal)
            $answer = null;
        }

        if (!is_string($answer) || '' === $answer) {
            throw new \RuntimeException(sprintf('Unable to resolve the variable "%s". Set the "%s" environment variable or run the command interactively.', $value, self::ENV_PREFIX.$name));
        }

        return $answer;
    }
}
