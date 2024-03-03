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

use Automate\Model\Server;

/**
 * Server Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class ServerDenormalizer extends AbstractDenormalizer
{
    /**
     * @param array<string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $server = new Server();

        $server
            ->setName($this->extractValue($data, 'name'))
            ->setHost($this->extractValue($data, 'host'))
            ->setUser($this->extractValue($data, 'user'))
            ->setSshKey($this->extractValue($data, 'ssh_key'))
            ->setPassword($this->extractValue($data, 'password', ''))
            ->setPath($this->extractValue($data, 'path'))
            ->setSharedPath($this->extractValue($data, 'shared_path'))
            ->setPort($this->extractValue($data, 'port', 22));

        return $server;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Server::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Server::class => true,
        ];
    }
}
