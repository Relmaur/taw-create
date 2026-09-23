<?php

declare(strict_types=1);

namespace TAW\Create\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TAW\Create\Choice;
use TAW\Create\Starter;

final class ChoiceTest extends TestCase
{
    public function testEnvironmentVariableWinsOverThePrompt(): void
    {
        $prompt = function (array $labels): int {
            $this->fail('The prompt must not run when TAW_STARTER is set.');
        };

        self::assertSame('gutenberg', Choice::starter('gutenberg', $prompt)->key);
    }

    public function testEnvironmentValueIsTrimmedAndCaseInsensitive(): void
    {
        self::assertSame('gutenberg', Choice::starter('  Gutenberg ', null)->key);
    }

    public function testUnknownEnvironmentValueIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown TAW starter "bricks". Use one of: theme, gutenberg.');

        Choice::starter('bricks', null);
    }

    public function testEmptyEnvironmentValueCountsAsUnset(): void
    {
        self::assertSame('theme', Choice::starter('', null)->key);
    }

    public function testNonInteractiveWithoutEnvironmentUsesTheDefault(): void
    {
        self::assertSame(Starter::default()->key, Choice::starter(false, null)->key);
        self::assertSame('theme', Choice::starter(false, null)->key);
    }

    public function testPromptOffersEveryStarterAndUsesTheAnswer(): void
    {
        $offered = [];
        $starter = Choice::starter(false, function (array $labels) use (&$offered): int {
            $offered = $labels;

            return 1;
        });

        self::assertSame('gutenberg', $starter->key);
        self::assertCount(2, $offered);
        self::assertStringStartsWith('taw-theme', $offered[0]);
        self::assertStringStartsWith('taw-gutenberg', $offered[1]);
    }

    public function testPromptAnswerOutOfRangeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Choice::starter(false, static fn (array $labels): int => 7);
    }

    public function testNoVersionMeansLatestStable(): void
    {
        self::assertNull(Choice::version(false));
        self::assertNull(Choice::version('  '));
    }

    #[DataProvider('validConstraints')]
    public function testVersionConstraintsAreAccepted(string $constraint): void
    {
        self::assertSame($constraint, Choice::version($constraint));
    }

    /** @return iterable<string, array{string}> */
    public static function validConstraints(): iterable
    {
        yield 'exact' => ['1.12.0'];
        yield 'caret' => ['^1.12'];
        yield 'tilde' => ['~0.1'];
        yield 'branch' => ['dev-main'];
        yield 'range' => ['>=1.0 <2.0'];
        yield 'or' => ['1.0 || 2.0'];
    }

    #[DataProvider('invalidConstraints')]
    public function testUnsafeVersionValuesAreRejected(string $constraint): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Choice::version($constraint);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidConstraints(): iterable
    {
        yield 'command substitution' => ['$(rm -rf /)'];
        yield 'semicolon' => ['1.0; echo hi'];
        yield 'backtick' => ['`id`'];
        yield 'option injection via quotes' => ['1.0" --prefer-source'];
        yield 'too long' => [str_repeat('1', 65)];
        yield 'leading dash is an option, not a version' => ['--prefer-source'];
    }
}
