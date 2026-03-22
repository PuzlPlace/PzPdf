<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageValign;
use ValueError;

#[CoversClass(PzPdfImageValign::class)]
final class ImageValignTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfImageValign, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Top' => [PzPdfImageValign::Top, 'top'],
            'Center' => [PzPdfImageValign::Center, 'center'],
            'Bottom' => [PzPdfImageValign::Bottom, 'bottom'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfImageValign $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(3, PzPdfImageValign::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfImageValign $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfImageValign::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfImageValign::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfImageValign::tryFrom('invalid'));
    }
}
