<?php

declare(strict_types=1);

namespace TAW\Create;

/**
 * Runs an external command. An interface so tests can replace the real
 * process with a fake that writes files instead.
 */
interface Runner
{
    /**
     * @param list<string> $command Program and arguments. Never a shell string.
     * @return int Exit code.
     */
    public function run(array $command, string $cwd): int;
}
