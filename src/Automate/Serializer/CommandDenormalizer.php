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
use Automate\Model\Project;
use Automate\Model\Command;

/**
 * Project Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class CommandDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $command = new Command();

        if(is_string($data)) {
            $command->setCmd($data);

            return $command;
        }

        $command
            ->setCmd($this->extractValue($data,  'cmd'))
            ->setOnly($this->extractValue($data, 'only'))
        ;

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Command::class;
    }
}
