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

    /**
     * @param array<string, mixed> $data
     */
    protected function extractValue(array $data, string $key, mixed $default = null): mixed
    {
        return $data[$key] ?? $default;
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer = $normalizer;
    }
}
