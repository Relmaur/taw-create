<?php

declare(strict_types=1);

namespace TAW\Create;

/**
 * Replaces the freshly created installer project with the chosen TAW theme.
 *
 * `composer create-project taw/create my-theme` first installs *this*
 * package into my-theme/. Its post-create-project-cmd then:
 *   1. runs `composer create-project <theme> <parent>/.taw-create-<random>`
 *      (a sibling folder, so the move below stays on one filesystem; the dot
 *      prefix hides it from WordPress, which skips dot folders);
 *   2. deletes the installer's own files from my-theme/;
 *   3. moves the theme's files into my-theme/ and removes the temp folder.
 *
 * Safety rules:
 *   - my-theme/ must contain only files this installer ships. Anything else
 *     means it isn't a fresh installer project, and nothing is deleted.
 *   - If the theme install fails, my-theme/ is left untouched and the temp
 *     folder is removed.
 *   - If a move fails halfway, the temp folder is kept and its path is in
 *     the error, so nothing is lost.
 */
final class Project
{
    /**
     * Every top-level entry the installer package can contain, whether it was
     * installed from a dist archive or from source.
     */
    public const OWN_ENTRIES = [
        '.editorconfig', '.git', '.gitattributes', '.github', '.gitignore',
        'AGENTS.md', 'CLAUDE.md', 'LICENSE', 'README.md',
        'composer.json', 'composer.lock', 'docs', 'phpstan.neon', 'phpunit.xml',
        'src', 'stubs', 'tests', 'tools', 'vendor',
    ];

    /**
     * @param list<string>            $composer Command that runs Composer, e.g. [PHP_BINARY, '/path/composer.phar'].
     * @param callable(string): void  $log      Receives progress lines.
     */
    public function __construct(
        private readonly Runner $runner,
        private readonly array $composer,
        private $log,
    ) {
    }

    /**
     * @throws \RuntimeException When the target isn't a fresh installer project, or the install or move fails.
     */
    public function install(string $target, Starter $starter, ?string $version, bool $interactive, bool $devMode): void
    {
        $target = rtrim($target, '/\\');
        if (!is_dir($target)) {
            throw new \RuntimeException(sprintf('Project folder "%s" does not exist.', $target));
        }

        $unexpected = array_values(array_diff(self::entries($target), self::OWN_ENTRIES));
        if ($unexpected !== []) {
            throw new \RuntimeException(sprintf(
                'Refusing to replace "%s": it contains files the TAW installer did not create (%s). Nothing was changed.',
                $target,
                implode(', ', $unexpected)
            ));
        }

        $temp = dirname($target) . DIRECTORY_SEPARATOR . '.taw-create-' . bin2hex(random_bytes(4));

        ($this->log)(sprintf('Installing %s into a temporary folder…', $starter->label));
        $exit = $this->runner->run($this->command($starter, $temp, $version, $interactive, $devMode), dirname($target));

        if ($exit !== 0 || !is_dir($temp)) {
            self::remove($temp);
            throw new \RuntimeException(sprintf(
                '`composer create-project %s` failed (exit code %d). "%s" was left as it was.',
                $starter->package,
                $exit,
                $target
            ));
        }

        foreach (self::entries($target) as $entry) {
            self::remove($target . DIRECTORY_SEPARATOR . $entry);
        }

        foreach (self::entries($temp) as $entry) {
            if (!@rename($temp . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry)) {
                throw new \RuntimeException(sprintf(
                    'Could not move "%s" into "%s". The complete theme is still in "%s"; move its contents by hand.',
                    $entry,
                    $target,
                    $temp
                ));
            }
        }

        rmdir($temp);
    }

    /**
     * The nested create-project command, as an argument list.
     *
     * @return list<string>
     */
    public function command(Starter $starter, string $temp, ?string $version, bool $interactive, bool $devMode): array
    {
        $command = [...$this->composer, 'create-project', $starter->package, $temp];

        if ($version !== null) {
            $command[] = $version;
        }

        $command[] = '--repository=' . json_encode(
            ['type' => 'vcs', 'url' => $starter->repository],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        if (!$interactive) {
            $command[] = '--no-interaction';
        }
        if (!$devMode) {
            $command[] = '--no-dev';
        }

        return $command;
    }

    /**
     * Top-level entry names in $dir, without "." and "..".
     *
     * @return list<string>
     */
    private static function entries(string $dir): array
    {
        $entries = scandir($dir);

        return $entries === false ? [] : array_values(array_diff($entries, ['.', '..']));
    }

    /**
     * Delete a file, symlink or folder tree. Symlinks are removed, never
     * followed, so a link can't lead the delete outside the project.
     */
    private static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (self::entries($path) as $entry) {
            self::remove($path . DIRECTORY_SEPARATOR . $entry);
        }

        rmdir($path);
    }
}
