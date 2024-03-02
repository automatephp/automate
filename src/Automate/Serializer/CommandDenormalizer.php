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
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $command = new Command();

        $command
            ->setCmd($this->extractValue($data, 'cmd'))
            ->setOnly($this->extractValue($data, 'only'));

        return $command;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Command::class === $type;
    }
}
