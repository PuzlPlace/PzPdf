<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfLineJoin;
use ValueError;

#[CoversClass(PzPdfLineJoin::class)]
final class LineJoinTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfLineJoin, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Miter' => [PzPdfLineJoin::Miter, 'miter'],
            'Round' => [PzPdfLineJoin::Round, 'round'],
            'Bevel' => [PzPdfLineJoin::Bevel, 'bevel'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfLineJoin $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(3, PzPdfLineJoin::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfLineJoin $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfLineJoin::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfLineJoin::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfLineJoin::tryFrom('invalid'));
    }
}
