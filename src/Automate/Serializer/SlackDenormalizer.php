<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Romaric Paul <romaric.paul@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Serializer;

use Automate\Model\Slack;

/**
 * Slack Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class SlackDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $slack = new Slack();

        $slack
            ->setHookUri($this->extractValue($data,            'hook_uri'))
            ->setDeploySuccessedMsg($this->extractValue($data, 'deploy_successed_msg'))
            ->setDeployFailedMsg($this->extractValue($data,    'deploy_failed_msg'))
        ;

        return $slack;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Slack::class;
    }
}
