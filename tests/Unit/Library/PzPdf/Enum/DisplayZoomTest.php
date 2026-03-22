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
    public function testCaseHasCorrectBackedValue(PzPdfDisplayZoom $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfDisplayZoom::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfDisplayZoom $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfDisplayZoom::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfDisplayZoom::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfDisplayZoom::tryFrom('invalid'));
    }
}
