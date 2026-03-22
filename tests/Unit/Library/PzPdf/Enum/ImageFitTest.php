<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageFit;
use ValueError;

#[CoversClass(PzPdfImageFit::class)]
final class ImageFitTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfImageFit, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'None' => [PzPdfImageFit::None, 'none'],
            'Fit' => [PzPdfImageFit::Fit, 'fit'],
            'Cover' => [PzPdfImageFit::Cover, 'cover'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfImageFit $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(3, PzPdfImageFit::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfImageFit $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfImageFit::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfImageFit::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfImageFit::tryFrom('invalid'));
    }
}
