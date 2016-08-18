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

/**
 * Project Denormalizer
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class ProjectDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $project = new Project();

        $project
            ->setRepository($this->extractValue($data,    'repository'))
            ->setSharedFiles($this->extractValue($data,   'shared_files', array()))
            ->setSharedFolders($this->extractValue($data, 'shared_folders', array()))
            ->setPreDeploy($this->extractValue($data,     'pre_deploy', array()))
            ->setOnDeploy($this->extractValue($data,      'on_deploy', array()))
            ->setPostDeploy($this->extractValue($data,    'post_deploy', array()))
        ;

        $platforms = $this->extractValue($data, 'platforms', array());

        foreach($platforms as $name => $platformData) {
            $platformData['name'] = $name;
            $platform = $this->normalizer->denormalize($platformData, Platform::class);

            $project->addPlatform($platform);
        }

        return $project;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Project::class;
    }
}