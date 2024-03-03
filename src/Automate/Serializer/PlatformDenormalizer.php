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

use Automate\Model\Platform;
use Automate\Model\Server;

/**
 * Platform Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class PlatformDenormalizer extends AbstractDenormalizer
{
    /**
     * @param array<string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $platform = new Platform();

        $platform
            ->setName($this->extractValue($data, 'name'))
            ->setDefaultBranch($this->extractValue($data, 'default_branch'))
            ->setMaxReleases($this->extractValue($data, 'max_releases', 5));

        $servers = $this->extractValue($data, 'servers', []);

        foreach ($servers as $name => $serverData) {
            $serverData['name'] = $name;
            $server = $this->normalizer->denormalize($serverData, Server::class);

            $platform->addServer($server);
        }

        return $platform;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Platform::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Platform::class => true,
        ];
    }
}
