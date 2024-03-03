<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Serializer;

use Automate\Model\Command;
use Automate\Model\Project;

/**
 * Project Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class CommandDenormalizer extends AbstractDenormalizer
{
    /**
     * @param array<string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $command = new Command();

        $command
            ->setCmd($this->extractValue($data, 'cmd'))
            ->setOnly($this->extractValue($data, 'only'));

        return $command;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Command::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Command::class => true,
        ];
    }
}
