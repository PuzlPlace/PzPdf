<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfDisplayLayout;
use ValueError;

#[CoversClass(PzPdfDisplayLayout::class)]
final class DisplayLayoutTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfDisplayLayout, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Single' => [PzPdfDisplayLayout::Single, 'single'],
            'Continuous' => [PzPdfDisplayLayout::Continuous, 'continuous'],
            'Two' => [PzPdfDisplayLayout::Two, 'two'],
            'Default' => [PzPdfDisplayLayout::Default, 'default'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfDisplayLayout $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfDisplayLayout::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfDisplayLayout $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfDisplayLayout::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfDisplayLayout::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfDisplayLayout::tryFrom('invalid'));
    }
}
