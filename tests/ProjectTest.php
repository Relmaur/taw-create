<?php

declare(strict_types=1);

namespace TAW\Create\Tests;

use PHPUnit\Framework\TestCase;
use TAW\Create\Project;
use TAW\Create\Runner;
use TAW\Create\Starter;

final class ProjectTest extends TestCase
{
    private string $root;
    private string $target;

    /** @var list<string> */
    private array $log = [];

    protected function setUp(): void
    {
        $this->root   = sys_get_temp_dir() . '/taw-create-test-' . bin2hex(random_bytes(4));
        $this->target = $this->root . '/my-theme';

        // What `composer create-project taw/create my-theme` leaves behind.
        mkdir($this->target . '/vendor/composer', 0777, true);
        mkdir($this->target . '/src');
        file_put_contents($this->target . '/composer.json', '{"name":"taw/create"}');
        file_put_contents($this->target . '/composer.lock', '{}');
        file_put_contents($this->target . '/vendor/autoload.php', '<?php');
        file_put_contents($this->target . '/src/Installer.php', '<?php');
        file_put_contents($this->target . '/README.md', '# taw/create');
    }

    protected function tearDown(): void
    {
        self::rrmdir($this->root);
    }

    public function testReplacesTheInstallerWithTheTheme(): void
    {
        $runner = new FakeRunner(static function (array $command): int {
            // [php, composer.phar, create-project, package, destination, …]
            $dest = $command[4];
            mkdir($dest . '/templates', 0777, true);
            file_put_contents($dest . '/style.css', '/* Theme Name: TAW Gutenberg */');
            file_put_contents($dest . '/templates/index.html', '<!-- wp:post-content /-->');
            file_put_contents($dest . '/composer.json', '{"name":"taw/gutenberg"}');
            file_put_contents($dest . '/.gitignore', '/vendor/');

            return 0;
        });

        $this->project($runner)->install($this->target, Starter::get('gutenberg'), null, false, true);

        self::assertSame(
            ['.gitignore', 'composer.json', 'style.css', 'templates'],
            self::listing($this->target)
        );
        self::assertSame('{"name":"taw/gutenberg"}', file_get_contents($this->target . '/composer.json'));
        self::assertFileExists($this->target . '/templates/index.html');
        self::assertSame(['my-theme'], self::listing($this->root), 'The temp folder must be gone.');
        self::assertSame($this->root, $runner->cwd, 'create-project runs in the parent folder.');
    }

    public function testFailedInstallLeavesTheTargetUntouchedAndRemovesTheTempFolder(): void
    {
        $before = self::listing($this->target);
        $runner = new FakeRunner(static function (array $command): int {
            mkdir($command[4]);
            file_put_contents($command[4] . '/partial.txt', 'half-downloaded');

            return 1;
        });

        try {
            $this->project($runner)->install($this->target, Starter::get('theme'), null, false, true);
            self::fail('A failed install must throw.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('failed (exit code 1)', $e->getMessage());
        }

        self::assertSame($before, self::listing($this->target));
        self::assertSame(['my-theme'], self::listing($this->root));
    }

    public function testRefusesATargetWithFilesTheInstallerDidNotCreate(): void
    {
        file_put_contents($this->target . '/client-notes.txt', 'do not delete');
        $runner = new FakeRunner(function (array $command): int {
            $this->fail('Nothing may be installed into an unexpected target.');
        });

        try {
            $this->project($runner)->install($this->target, Starter::get('theme'), null, false, true);
            self::fail('An unexpected file must stop the install.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('client-notes.txt', $e->getMessage());
        }

        self::assertFileExists($this->target . '/client-notes.txt');
        self::assertFileExists($this->target . '/composer.json');
    }

    public function testDeletingNeverFollowsSymlinksOutOfTheProject(): void
    {
        $outside = $this->root . '/outside';
        mkdir($outside);
        file_put_contents($outside . '/keep.txt', 'precious');
        self::rrmdir($this->target . '/vendor');
        symlink($outside, $this->target . '/vendor');

        $runner = new FakeRunner(static function (array $command): int {
            mkdir($command[4]);
            file_put_contents($command[4] . '/style.css', '');

            return 0;
        });

        $this->project($runner)->install($this->target, Starter::get('theme'), null, false, true);

        self::assertFileExists($outside . '/keep.txt');
        self::assertSame(['style.css'], self::listing($this->target));
    }

    public function testCommandIsAnArgumentListWithTheStartersRepository(): void
    {
        $command = $this->project(new FakeRunner(static fn (): int => 0))
            ->command(Starter::get('gutenberg'), '/sites/themes/.taw-create-ab12', '^0.1', false, false);

        self::assertSame([
            '/usr/bin/php', '/opt/composer.phar',
            'create-project', 'taw/gutenberg', '/sites/themes/.taw-create-ab12', '^0.1',
            '--repository={"type":"vcs","url":"https://github.com/Relmaur/taw-gutenberg"}',
            '--no-interaction',
            '--no-dev',
        ], $command);
    }

    public function testInteractiveDevInstallAddsNoFlags(): void
    {
        $command = $this->project(new FakeRunner(static fn (): int => 0))
            ->command(Starter::get('theme'), '/tmp/x', null, true, true);

        self::assertSame(
            '--repository={"type":"vcs","url":"https://github.com/Relmaur/taw-theme"}',
            end($command)
        );
        self::assertNotContains('--no-interaction', $command);
        self::assertNotContains('--no-dev', $command);
    }

    public function testTempFolderIsAHiddenSiblingOfTheTarget(): void
    {
        $runner = new FakeRunner(static function (array $command): int {
            mkdir($command[4]);

            return 0;
        });

        $this->project($runner)->install($this->target, Starter::get('theme'), null, false, true);

        $temp = $runner->command[4] ?? '';
        self::assertSame($this->root, dirname($temp));
        self::assertMatchesRegularExpression('/^\.taw-create-[0-9a-f]{8}$/', basename($temp));
    }

    private function project(Runner $runner): Project
    {
        return new Project($runner, ['/usr/bin/php', '/opt/composer.phar'], function (string $line): void {
            $this->log[] = $line;
        });
    }

    /**
     * @return list<string>
     */
    private static function listing(string $dir): array
    {
        $entries = array_values(array_diff((array) scandir($dir), ['.', '..']));
        sort($entries);

        return $entries;
    }

    private static function rrmdir(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
            self::rrmdir($path . '/' . $entry);
        }
        rmdir($path);
    }
}
