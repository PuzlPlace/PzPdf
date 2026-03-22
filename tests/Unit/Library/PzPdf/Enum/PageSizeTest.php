<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use ValueError;

#[CoversClass(PzPdfPageSize::class)]
final class PageSizeTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfPageSize, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'A4' => [PzPdfPageSize::A4, 'A4'],
            'Letter' => [PzPdfPageSize::Letter, 'Letter'],
            'Legal' => [PzPdfPageSize::Legal, 'Legal'],
            'A3' => [PzPdfPageSize::A3, 'A3'],
            'A5' => [PzPdfPageSize::A5, 'A5'],
            'A6' => [PzPdfPageSize::A6, 'A6'],
            'B4' => [PzPdfPageSize::B4, 'B4'],
            'B5' => [PzPdfPageSize::B5, 'B5'],
            'Folio' => [PzPdfPageSize::Folio, 'Folio'],
            'Executive' => [PzPdfPageSize::Executive, 'Executive'],
            'Tabloid' => [PzPdfPageSize::Tabloid, 'Tabloid'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfPageSize $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(11, PzPdfPageSize::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfPageSize $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfPageSize::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfPageSize::from('Unknown');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfPageSize::tryFrom('Unknown'));
    }
}
