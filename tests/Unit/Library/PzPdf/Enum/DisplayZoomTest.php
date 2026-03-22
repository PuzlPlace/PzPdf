<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfDisplayZoom;
use ValueError;

#[CoversClass(PzPdfDisplayZoom::class)]
final class DisplayZoomTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfDisplayZoom, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'FullPage' => [PzPdfDisplayZoom::FullPage, 'fullpage'],
            'FullWidth' => [PzPdfDisplayZoom::FullWidth, 'fullwidth'],
            'Real' => [PzPdfDisplayZoom::Real, 'real'],
            'Default' => [PzPdfDisplayZoom::Default, 'default'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfDisplayZoom $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, PzPdfDisplayZoom::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfDisplayZoom $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfDisplayZoom::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfDisplayZoom::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfDisplayZoom::tryFrom('invalid'));
    }
}
