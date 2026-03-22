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
    public function testCaseHasCorrectBackedValue(PzPdfFontStyle $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfFontStyle::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfFontStyle $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfFontStyle::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfFontStyle::from('X');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfFontStyle::tryFrom('X'));
    }

    public function testRegularHasEmptyStringValue(): void
    {
        $this->assertSame('', PzPdfFontStyle::Regular->value);
    }
}
