<?php

declare(strict_types=1);

namespace TAW\Create;

use Composer\IO\IOInterface;

/**
 * Asks which starter to install, through Composer's own IO.
 */
final class Prompt
{
    public function __construct(private readonly IOInterface $io)
    {
    }

    /**
     * @param list<string> $labels
     * @return int Index of the chosen label.
     */
    public function __invoke(array $labels): int
    {
        $answer = $this->io->select('<question>Which TAW theme do you want to start from?</question>', $labels, '0');

        return is_numeric($answer) ? (int) $answer : 0;
    }
}
