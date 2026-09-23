<?php

declare(strict_types=1);

namespace TAW\Create;

/**
 * Decides which starter to install and which version constraint to use.
 *
 * `composer create-project` passes no extra arguments to the project's
 * scripts, so the choice comes from, in order:
 *   1. the TAW_STARTER environment variable (scripts and CI);
 *   2. a prompt, when the terminal is interactive;
 *   3. the default starter (taw-theme), when it isn't.
 *
 * Environment values are untrusted: anything that isn't an exact starter key
 * or a plausible Composer constraint is rejected rather than passed on.
 */
final class Choice
{
    /**
     * Characters a Composer version constraint can contain ("^1.2", "~0.1",
     * "1.12.0", "dev-main", ">=1.0 <2.0", "1.0 || 2.0"). Everything else,
     * including shell metacharacters, is refused, and so is a leading "-":
     * Composer would read "--prefer-source" as an option, not a version.
     */
    private const CONSTRAINT_PATTERN = '/^(?!-)[A-Za-z0-9.^~*|<>=@ ,_-]{1,64}$/';

    /**
     * @param string|false $env Value of TAW_STARTER, or false when unset.
     * @param (callable(list<string>): int)|null $prompt Asks the user to pick an index; null when not interactive.
     */
    public static function starter(string|false $env, ?callable $prompt): Starter
    {
        if ($env !== false && trim($env) !== '') {
            return Starter::get(strtolower(trim($env)));
        }

        if ($prompt === null) {
            return Starter::default();
        }

        $starters = array_values(Starter::all());
        $index    = $prompt(array_map(static fn (Starter $s): string => $s->label, $starters));

        if (!isset($starters[$index])) {
            throw new \InvalidArgumentException(sprintf('No TAW starter at position %d.', $index));
        }

        return $starters[$index];
    }

    /**
     * The version constraint for the nested create-project, or null for the
     * latest stable release.
     *
     * @param string|false $env Value of TAW_STARTER_VERSION, or false when unset.
     */
    public static function version(string|false $env): ?string
    {
        if ($env === false || trim($env) === '') {
            return null;
        }

        $constraint = trim($env);
        if (preg_match(self::CONSTRAINT_PATTERN, $constraint) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'TAW_STARTER_VERSION "%s" is not a Composer version constraint.',
                $constraint
            ));
        }

        return $constraint;
    }
}
