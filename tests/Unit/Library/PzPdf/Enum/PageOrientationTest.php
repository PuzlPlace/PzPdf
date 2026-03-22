<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use ValueError;

#[CoversClass(PzPdfPageOrientation::class)]
final class PageOrientationTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfPageOrientation, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Portrait' => [PzPdfPageOrientation::Portrait, 'P'],
            'Landscape' => [PzPdfPageOrientation::Landscape, 'L'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfPageOrientation $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(2, PzPdfPageOrientation::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfPageOrientation $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfPageOrientation::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfPageOrientation::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfPageOrientation::tryFrom('X'));
    }
}
