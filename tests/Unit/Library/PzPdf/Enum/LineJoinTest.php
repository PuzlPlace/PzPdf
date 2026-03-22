<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfLineJoin;
use ValueError;

#[CoversClass(PzPdfLineJoin::class)]
final class LineJoinTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfLineJoin, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Miter' => [PzPdfLineJoin::Miter, 'miter'],
            'Round' => [PzPdfLineJoin::Round, 'round'],
            'Bevel' => [PzPdfLineJoin::Bevel, 'bevel'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfLineJoin $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, PzPdfLineJoin::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfLineJoin $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfLineJoin::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfLineJoin::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfLineJoin::tryFrom('invalid'));
    }
}
