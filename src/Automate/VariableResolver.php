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

    private const string NAME_PATTERN = '[a-z][a-z0-9_]*';

    /** @var array<string, true> */
    private array $passwordVariables = [];

    /**
     * @param array<string, string> $variables values given with --var, they take precedence over the environment
     */
    public function __construct(private readonly SymfonyStyle $io, private array $variables = [])
    {
    }

    /**
     * @param string[] $options raw "name=value" options
     *
     * @return array<string, string>
     */
    public static function parseCliVariables(array $options): array
    {
        $variables = [];
        foreach ($options as $option) {
            $parts = explode('=', $option, 2);
            if (2 !== count($parts) || 1 !== preg_match('/^'.self::NAME_PATTERN.'$/', $parts[0])) {
                throw new \InvalidArgumentException(sprintf('Invalid variable "%s": expected "name=value" with a name matching %s.', $option, self::NAME_PATTERN));
            }

            $variables[$parts[0]] = $parts[1];
        }

        return $variables;
    }

    /**
     * Replace %name% tokens in every string of the configuration, for the requested platform only
     * so that the secrets and variables of the other platforms are never required.
     *
     * @param array<string, mixed> $config normalized configuration
     *
     * @return array<string, mixed>
     */
    public function resolveConfiguration(array $config, string $platform): array
    {
        foreach ($config as $key => $value) {
            if ('platforms' === $key && is_array($value)) {
                if (isset($value[$platform])) {
                    $config[$key][$platform] = $this->resolveValue($value[$platform], 'platforms.'.$platform);
                }

                continue;
            }

            $config[$key] = $this->resolveValue($value, (string) $key);
        }

        return $config;
    }

    /**
     * @deprecated use resolveConfiguration() through the Loader
     */
    public function process(Platform $platform): void
    {
        foreach ($platform->getServers() as $server) {
            $password = $server->getPassword();
            if ($password && 1 === preg_match('/^%('.self::NAME_PATTERN.')%$/', $password, $matches)) {
                $server->setPassword($this->resolveVariable($matches[1]));
                $this->passwordVariables[$matches[1]] = true;
            }
        }
    }

    /**
     * Every resolved variable, passwords included, to reload the same configuration in the "exec" child process.
     *
     * @return array<string, string>
     */
    public function getEnvVariables(): array
    {
        $envs = [];
        foreach ($this->variables as $name => $variable) {
            $envs[self::ENV_PREFIX.$name] = $variable;
        }

        return $envs;
    }

    /**
     * Variables that are safe to expose to hooks and to the console.
     *
     * @return array<string, string>
     */
    public function getHookVariables(): array
    {
        return array_diff_key($this->variables, $this->passwordVariables);
    }

    private function resolveValue(mixed $value, string $path): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->resolveValue($item, $path.'.'.$key);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // a password is a variable only when it is entirely "%name%", anything else is a literal secret
        if (1 === preg_match('/^platforms\.[^.]+\.servers\.[^.]+\.password$/', $path)) {
            if (1 !== preg_match('/^%('.self::NAME_PATTERN.')%$/', $value, $matches)) {
                return $value;
            }

            $this->passwordVariables[$matches[1]] = true;

            return $this->resolveVariable($matches[1]);
        }

        return preg_replace_callback(
            '/%%|%('.self::NAME_PATTERN.')%/',
            fn (array $match): string => isset($match[1]) ? $this->resolveVariable($match[1], $path) : '%',
            $value,
        ) ?? $value;
    }

    /**
     * @param ?string $path configuration path of the token, null for a password field (the only one allowed to prompt)
     */
    private function resolveVariable(string $name, ?string $path = null): string
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        }

        $env = getenv(self::ENV_PREFIX.$name);
        if (false !== $env && '' !== $env) {
            return $this->variables[$name] = $env;
        }

        if (null !== $path) {
            throw new \RuntimeException(sprintf('Unable to resolve the variable "%%%s%%" used in "%s". Pass --var %s=<value> or set the "%s" environment variable.', $name, $path, $name, self::ENV_PREFIX.$name));
        }

        try {
            $answer = $this->io->askHidden(sprintf('Enter a value for password "%s"', $name));
        } catch (MissingInputException) {
            // no input stream available (non-interactive terminal)
            $answer = null;
        }

        if (!is_string($answer) || '' === $answer) {
            throw new \RuntimeException(sprintf('Unable to resolve the variable "%%%s%%". Set the "%s" environment variable or run the command interactively.', $name, self::ENV_PREFIX.$name));
        }

        return $this->variables[$name] = $answer;
    }
}
