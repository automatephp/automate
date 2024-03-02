<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Command;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends BaseCommand
{
    public const PACKAGE_NAME = 'automate/automate';

    public const FILE_NAME = 'automate.phar';

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Self update.')
            ->addOption(
                'unstable',
                'u',
                InputOption::VALUE_NONE,
                'Update to most recent pre-release version.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updater = new Updater(null, false, Updater::STRATEGY_GITHUB);
        /** @var GithubStrategy $strategy */
        $strategy = $updater->getStrategy();
        $strategy->setPackageName(self::PACKAGE_NAME);
        $strategy->setPharName(self::FILE_NAME);
        $strategy->setCurrentLocalVersion($this->getApplication()->getVersion());

        if ($input->getOption('unstable')) {
            $strategy->setStability(GithubStrategy::UNSTABLE);
        }

        $output->writeln('Updating...'.PHP_EOL);

        try {
            $result = $updater->update();
            $newVersion = $updater->getNewVersion();
            $oldVersion = $updater->getOldVersion();
            if (40 == strlen($newVersion)) {
                $newVersion = 'dev-'.$newVersion;
            }

            if (40 == strlen($oldVersion)) {
                $oldVersion = 'dev-'.$oldVersion;
            }

            if ($result) {
                $output->writeln('<fg=green>Automate has been updated.</fg=green>');
                $output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $newVersion
                ));
                $output->writeln(sprintf(
                    '<fg=green>Previous version was:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            } else {
                $output->writeln('<fg=green>Automate is currently up to date.</fg=green>');
                $output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            }
        } catch (\Exception $exception) {
            $output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $exception->getMessage()));
        }

        if (!$input->getOption('unstable')) {
            $output->write(PHP_EOL);
            $output->writeln('You can also select update to unstable version using --unstable.');
        }

        return 0;
    }
}
