<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use ValueError;

#[CoversClass(PzPdfOutputMode::class)]
final class OutputModeTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfOutputMode, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Inline' => [PzPdfOutputMode::Inline, 'I'],
            'Download' => [PzPdfOutputMode::Download, 'D'],
            'File' => [PzPdfOutputMode::File, 'F'],
            'String' => [PzPdfOutputMode::String, 'S'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfOutputMode $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfOutputMode::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfOutputMode $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfOutputMode::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfOutputMode::from('X');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfOutputMode::tryFrom('X'));
    }
}
