<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfPermission;
use ValueError;

#[CoversClass(PzPdfPermission::class)]
final class PdfPermissionTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfPermission, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Copy' => [PzPdfPermission::Copy, 'copy'],
            'Print' => [PzPdfPermission::Print, 'print'],
            'Modify' => [PzPdfPermission::Modify, 'modify'],
            'AnnotForms' => [PzPdfPermission::AnnotForms, 'annot-forms'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testCaseHasCorrectBackedValue(PzPdfPermission $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function testAllCasesExist(): void
    {
        $this->assertCount(4, PzPdfPermission::cases());
    }

    #[DataProvider('casesProvider')]
    public function testFromReturnsCorrectCase(PzPdfPermission $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfPermission::from($value));
    }

    public function testFromThrowsValueErrorForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        PzPdfPermission::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(PzPdfPermission::tryFrom('invalid'));
    }
}
