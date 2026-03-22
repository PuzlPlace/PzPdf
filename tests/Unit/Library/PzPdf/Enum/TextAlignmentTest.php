<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use ValueError;

#[CoversClass(PzPdfTextAlignment::class)]
final class TextAlignmentTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfTextAlignment, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Left' => [PzPdfTextAlignment::Left, 'L'],
            'Center' => [PzPdfTextAlignment::Center, 'C'],
            'Right' => [PzPdfTextAlignment::Right, 'R'],
            'Justify' => [PzPdfTextAlignment::Justify, 'J'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfTextAlignment $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfTextAlignment::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfTextAlignment $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfTextAlignment::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfTextAlignment::from('X');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfTextAlignment::tryFrom('X'));
    }
}
