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
    public function test_case_has_correct_backed_value(PzPdfPageSize $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(11, PzPdfPageSize::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfPageSize $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfPageSize::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfPageSize::from('Unknown');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfPageSize::tryFrom('Unknown'));
    }
}
