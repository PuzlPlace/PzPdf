<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageAlign;
use ValueError;

#[CoversClass(PzPdfImageAlign::class)]
final class ImageAlignTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfImageAlign, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Left' => [PzPdfImageAlign::Left, 'left'],
            'Center' => [PzPdfImageAlign::Center, 'center'],
            'Right' => [PzPdfImageAlign::Right, 'right'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfImageAlign $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(3, PzPdfImageAlign::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfImageAlign $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfImageAlign::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfImageAlign::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfImageAlign::tryFrom('invalid'));
    }
}
