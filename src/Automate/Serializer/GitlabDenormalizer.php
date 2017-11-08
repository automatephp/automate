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

use Automate\Model\Gitlab;

/**
 * Gitlab Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class GitlabDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $gitlab = new Gitlab();

        $gitlab
            ->setUri($this->extractValue($data,                'uri'))
            ->setIdProject($this->extractValue($data,          'id_project'))
            ->setTokenTrigger($this->extractValue($data,       'token_trigger'))
            ->setEnvironment($this->extractValue($data,        'environment'))
            ->setRef($this->extractValue($data,                'ref'))
            ->setDeploySuccessedMsg($this->extractValue($data, 'deploy_successed_msg'))
            ->setDeployFailedMsg($this->extractValue($data,    'deploy_failed_msg'))
        ;

        return $gitlab;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Gitlab::class;
    }
}
