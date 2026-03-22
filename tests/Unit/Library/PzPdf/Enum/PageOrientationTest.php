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
    public function testCaseHasCorrectBackedValue(PzPdfPageOrientation $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(2, PzPdfPageOrientation::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfPageOrientation $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfPageOrientation::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfPageOrientation::from('X');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfPageOrientation::tryFrom('X'));
    }
}
