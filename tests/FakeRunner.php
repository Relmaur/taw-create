<?php

declare(strict_types=1);

namespace TAW\Create\Tests;

use TAW\Create\Runner;

/**
 * Stands in for Composer: records the call and runs a callback that fakes
 * what create-project would write.
 */
final class FakeRunner implements Runner
{
    /** @var list<string> */
    public array $command = [];
    public string $cwd = '';

    /** @param \Closure(list<string>): int $onRun */
    public function __construct(private readonly \Closure $onRun)
    {
    }

    public function run(array $command, string $cwd): int
    {
        $this->command = $command;
        $this->cwd     = $cwd;

        return ($this->onRun)($command);
    }
}
