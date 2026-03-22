<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfLineCap;
use ValueError;

#[CoversClass(PzPdfLineCap::class)]
final class LineCapTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfLineCap, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Butt' => [PzPdfLineCap::Butt, 'butt'],
            'Round' => [PzPdfLineCap::Round, 'round'],
            'Square' => [PzPdfLineCap::Square, 'square'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfLineCap $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, PzPdfLineCap::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfLineCap $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfLineCap::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfLineCap::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfLineCap::tryFrom('invalid'));
    }
}
