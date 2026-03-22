<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfLineCap;
use ValueError;

#[CoversClass(PzPdfLineCap::class)]
final class LineCapTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfLineCap, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Butt' => [PzPdfLineCap::Butt, 'butt'],
            'Round' => [PzPdfLineCap::Round, 'round'],
            'Square' => [PzPdfLineCap::Square, 'square'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfLineCap $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(3, PzPdfLineCap::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfLineCap $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfLineCap::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfLineCap::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfLineCap::tryFrom('invalid'));
    }
}
