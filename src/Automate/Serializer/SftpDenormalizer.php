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
use Automate\Model\Sftp;

/**
 * Sftp Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class SftpDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $sftp = new Sftp();

        $sftp
            ->setExcludeFolders($this->extractCommands($data, 'exclude_folders', array()))
            ->setLocalBuild($this->extractCommands($data, 'local_build', array()))
        ;

        return $sftp;
    }

    public function extractCommands($data, $hookName)
    {
        $commands = [];

        $data = $this->extractValue($data, $hookName, array());
        foreach ($data as $item) {
            $commands[] = $this->normalizer->denormalize($item, Command::class);
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Sftp::class;
    }
}
