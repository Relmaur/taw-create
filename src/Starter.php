<?php

declare(strict_types=1);

namespace TAW\Create;

/**
 * One TAW theme that the installer can start a project from.
 *
 * The list is fixed on purpose: the installer only ever runs
 * `composer create-project` for these packages and repositories, never for a
 * name typed by the user.
 */
final class Starter
{
    private function __construct(
        public readonly string $key,
        public readonly string $package,
        public readonly string $repository,
        public readonly string $label,
        /** @var list<string> */
        public readonly array $nextSteps,
    ) {
    }

    /**
     * Every starter, keyed by the value TAW_STARTER accepts. The first one is
     * the default.
     *
     * @return array<string, self>
     */
    public static function all(): array
    {
        return [
            'theme' => new self(
                'theme',
                'taw/theme',
                'https://github.com/Relmaur/taw-theme',
                'taw-theme (classic: PHP blocks, Vite, Tailwind, Alpine)',
                [
                    'Activate the theme in wp-admin → Appearance → Themes.',
                    'Run `npm run dev` for the Vite dev server, or `npm run build` for production assets.',
                    'Read README.md, then run the `project-init` skill to set up CI and integrations.',
                ],
            ),
            'gutenberg' => new self(
                'gutenberg',
                'taw/gutenberg',
                'https://github.com/Relmaur/taw-gutenberg',
                'taw-gutenberg (block theme, full site editing)',
                [
                    'Activate the theme in wp-admin → Appearance → Themes.',
                    'Define data in taw-schema/*.json and check it with `php bin/taw schema:validate`.',
                    'Edit templates and styles in Appearance → Editor.',
                ],
            ),
        ];
    }

    public static function default(): self
    {
        $all = self::all();

        return $all[array_key_first($all)];
    }

    /**
     * @throws \InvalidArgumentException When $key isn't a known starter.
     */
    public static function get(string $key): self
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown TAW starter "%s". Use one of: %s.',
                $key,
                implode(', ', array_keys($all))
            ));
        }

        return $all[$key];
    }
}
