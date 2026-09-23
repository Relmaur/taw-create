<?php

declare(strict_types=1);

namespace TAW\Create;

/**
 * Runs a command with proc_open's array form: no shell is involved, so no
 * argument can be interpreted as shell syntax. The child shares this
 * process's terminal, so Composer's progress output and prompts work as usual.
 */
final class ProcessRunner implements Runner
{
    public function run(array $command, string $cwd): int
    {
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $cwd);
        if (!is_resource($process)) {
            return 1;
        }

        return proc_close($process);
    }
}
