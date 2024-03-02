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
    protected DenormalizerInterface|NormalizerInterface $normalizer;

    abstract public function denormalize($data, $class, $format = null, array $context = []);

    abstract public function supportsDenormalization($data, $type, $format = null): bool;

    protected function extractValue(array $data, string $key, mixed $default = null)
    {
        return $data[$key] ?? $default;
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }
}
