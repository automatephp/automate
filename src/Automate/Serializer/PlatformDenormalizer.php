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
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $platform = new Platform();

        $platform
            ->setName($this->extractValue($data,          'name'))
            ->setDefaultBranch($this->extractValue($data, 'default_branch'))
            ->setMaxReleases($this->extractValue($data,   'max_release', 5))
        ;

        $servers = $this->extractValue($data, 'servers', array());

        foreach ($servers as $name => $serverData) {
            $serverData['name'] = $name;
            $server = $this->normalizer->denormalize($serverData, Server::class);

            $platform->addServer($server);
        }

        return $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Platform::class;
    }
}
