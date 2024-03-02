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

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractDenormalizer implements DenormalizerInterface, NormalizerAwareInterface
{
    /**
     * @var DenormalizerInterface|NormalizerInterface
     */
    protected $normalizer;

    /**
     * {@inheritdoc}
     */
    abstract public function denormalize($data, $class, $format = null, array $context = []);

    /**
     * {@inheritdoc}
     */
    abstract public function supportsDenormalization($data, $type, $format = null);

    /**
     * Extract a value from a given array.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function extractValue(array $data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }
}
