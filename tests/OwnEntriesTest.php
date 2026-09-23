<?php

declare(strict_types=1);

namespace TAW\Create\Tests;

use PHPUnit\Framework\TestCase;
use TAW\Create\Project;

/**
 * Every top-level file in this repo ends up in the user's project folder
 * before the swap. If one is missing from Project::OWN_ENTRIES, the installer
 * refuses to run for everyone, so catch it here instead.
 */
final class OwnEntriesTest extends TestCase
{
    /** Local-only files that never reach an install (they're gitignored). */
    private const LOCAL_ONLY = ['.phpunit.cache', '.phpunit.result.cache', '.DS_Store', '.idea', '.vscode'];

    public function testEveryTopLevelEntryIsOwnedByTheInstaller(): void
    {
        $entries = array_diff((array) scandir(dirname(__DIR__)), ['.', '..'], self::LOCAL_ONLY);

        self::assertSame([], array_values(array_diff($entries, Project::OWN_ENTRIES)));
    }
}
