<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use ValueError;

#[CoversClass(PzPdfFontStyle::class)]
final class FontStyleTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfFontStyle, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Regular' => [PzPdfFontStyle::Regular, ''],
            'Bold' => [PzPdfFontStyle::Bold, 'B'],
            'Italic' => [PzPdfFontStyle::Italic, 'I'],
            'BoldItalic' => [PzPdfFontStyle::BoldItalic, 'BI'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfFontStyle $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, PzPdfFontStyle::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfFontStyle $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfFontStyle::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfFontStyle::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfFontStyle::tryFrom('X'));
    }

    public function test_regular_has_empty_string_value(): void
    {
        $this->assertSame('', PzPdfFontStyle::Regular->value);
    }
}
