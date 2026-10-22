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

            // the value is forwarded to the "exec" child process through its environment, where "" means unset
            if ('' === $parts[1]) {
                throw new \InvalidArgumentException(sprintf('Invalid variable "%s": the value cannot be empty.', $option));
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
        // passwords first, so that a variable used both as a password and elsewhere is prompted
        // whatever the order of the keys in the file
        $passwords = [];
        foreach ($config['platforms'][$platform]['servers'] ?? [] as $server => $serverConfig) {
            if (is_array($serverConfig) && is_string($serverConfig['password'] ?? null)) {
                $passwords[$server] = $this->resolvePassword($serverConfig['password']);
                unset($config['platforms'][$platform]['servers'][$server]['password']);
            }
        }

        foreach ($config as $key => $value) {
            if ('platforms' === $key && is_array($value)) {
                if (isset($value[$platform])) {
                    $config[$key][$platform] = $this->resolveValue($value[$platform], 'platforms.'.$platform);
                }

                continue;
            }

            $config[$key] = $this->resolveValue($value, (string) $key);
        }

        foreach ($passwords as $server => $password) {
            $config['platforms'][$platform]['servers'][$server]['password'] = $password;
        }

        return $config;
    }

    /**
     * @deprecated use resolveConfiguration() through the Loader
     */
    public function process(Platform $platform): void
    {
        foreach ($platform->getServers() as $server) {
            if ($server->getPassword()) {
                $server->setPassword($this->resolvePassword($server->getPassword()));
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
     * Variables that are safe to expose to hooks.
     *
     * @return array<string, string>
     */
    public function getHookVariables(): array
    {
        return array_diff_key($this->variables, $this->passwordVariables);
    }

    /**
     * Every resolved value, passwords included, to keep them out of the console output.
     *
     * @return array<string, string>
     */
    public function getMaskedValues(): array
    {
        return $this->variables;
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

        return preg_replace_callback(
            '/%%|%('.self::NAME_PATTERN.')%/',
            fn (array $match): string => isset($match[1]) ? $this->resolveVariable($match[1], $path) : '%',
            $value,
        ) ?? $value;
    }

    /**
     * A password is a variable only when it is entirely "%name%" (any name, as it always was),
     * anything else is a literal secret.
     */
    private function resolvePassword(string $value): string
    {
        if (1 !== preg_match('/^%([^%]+)%$/', $value, $matches)) {
            return $value;
        }

        $this->passwordVariables[$matches[1]] = true;

        return $this->resolveVariable($matches[1]);
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
            throw new \RuntimeException(sprintf('Unable to resolve the variable "%%%s%%" used in "%s". Pass --var %s=<value> or set the "%s" environment variable.', $name, $path, $name, self::ENV_PREFIX.$name).' If it is not a variable, write each literal "%" as "%%" (e.g. date +%%Y%%m%%d, printf \'%%s\').');
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
