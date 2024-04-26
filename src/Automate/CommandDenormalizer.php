<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Model\Action;
use Automate\Model\Command;
use Automate\Model\Upload;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CommandDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['cmd'])) {
            $action = new Command($data['cmd']);
        } elseif (isset($data['upload'])) {
            $action = new Upload(
                path: $data['upload'],
                exclude: $data['exclude'] ?? null
            );
        } else {
            throw new \InvalidArgumentException('Actions must have a "cmd" or "upload" parameter.');
        }

        $action->setOnly($data['only'] ?? null);

        return $action;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Action::class => true,
        ];
    }
}
