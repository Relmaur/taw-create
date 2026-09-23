<?php

declare(strict_types=1);

namespace TAW\Create;

use Composer\Script\Event;

/**
 * The post-create-project-cmd handler: the thin layer between Composer and
 * Choice/Project, which hold the logic and are unit-tested.
 *
 *   composer create-project taw/create my-theme --repository=…
 *   TAW_STARTER=gutenberg composer create-project taw/create my-theme --repository=…
 */
final class Installer
{
    public static function postCreateProject(Event $event): void
    {
        $io = $event->getIO();

        $prompt = $io->isInteractive() ? new Prompt($io) : null;

        $starter = Choice::starter(getenv('TAW_STARTER'), $prompt);
        $version = Choice::version(getenv('TAW_STARTER_VERSION'));

        if ($prompt === null) {
            $io->write(sprintf('<info>TAW starter:</info> %s (set TAW_STARTER=theme|gutenberg to choose)', $starter->label));
        }

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $target    = dirname(is_string($vendorDir) ? $vendorDir : (string) getcwd() . '/vendor');

        $project = new Project(new ProcessRunner(), self::composerCommand(), static function (string $line) use ($io): void {
            $io->write($line);
        });
        $project->install($target, $starter, $version, $io->isInteractive(), $event->isDevMode());

        $io->write('');
        $io->write(sprintf('<info>Done.</info> %s is ready in %s', $starter->label, $target));
        foreach ($starter->nextSteps as $step) {
            $io->write('  - ' . $step);
        }
    }

    /**
     * How to run the same Composer that is running this script. Composer
     * exports its own path as COMPOSER_BINARY to scripts (that's what
     * "@composer" in scripts uses).
     *
     * @return list<string>
     */
    private static function composerCommand(): array
    {
        $binary = getenv('COMPOSER_BINARY');

        return is_string($binary) && is_file($binary) ? [PHP_BINARY, $binary] : ['composer'];
    }
}
