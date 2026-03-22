<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\ValueObject\PzPdfPageDimension;

#[CoversClass(PzPdfPageDimension::class)]
final class PageDimensionTest extends TestCase
{
    public function testFromEnumCreatesWithEnumAndNullDimensions(): void
    {
        $dim = PzPdfPageDimension::fromEnum(PzPdfPageSize::A4);

        $this->assertSame(PzPdfPageSize::A4, $dim->enum);
        $this->assertNull($dim->width);
        $this->assertNull($dim->height);
    }

    public function testFromEnumToFpdfParamReturnsString(): void
    {
        $dim = PzPdfPageDimension::fromEnum(PzPdfPageSize::A4);

        $this->assertSame('A4', $dim->toFpdfParam());
    }

    public function testFromEnumWithLetterSize(): void
    {
        $dim = PzPdfPageDimension::fromEnum(PzPdfPageSize::Letter);

        $this->assertSame(PzPdfPageSize::Letter, $dim->enum);
        $this->assertSame('Letter', $dim->toFpdfParam());
    }

    public function testFromEnumWithLegalSize(): void
    {
        $dim = PzPdfPageDimension::fromEnum(PzPdfPageSize::Legal);

        $this->assertSame('Legal', $dim->toFpdfParam());
    }

    public function testCustomCreatesWithDimensionsAndNullEnum(): void
    {
        $dim = PzPdfPageDimension::custom(200.0, 300.0);

        $this->assertNull($dim->enum);
        $this->assertSame(200.0, $dim->width);
        $this->assertSame(300.0, $dim->height);
    }

    public function testCustomToFpdfParamReturnsArray(): void
    {
        $dim = PzPdfPageDimension::custom(200.0, 300.0);

        $this->assertSame([200.0, 300.0], $dim->toFpdfParam());
    }

    public function testCustomWithSmallDimensions(): void
    {
        $dim = PzPdfPageDimension::custom(0.1, 0.1);

        $this->assertSame([0.1, 0.1], $dim->toFpdfParam());
    }

    public function testCustomWithIntegerValues(): void
    {
        $dim = PzPdfPageDimension::custom(100, 200);

        $this->assertSame(100.0, $dim->width);
        $this->assertSame(200.0, $dim->height);
    }

    public function testCustomWithZeroWidthThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/Dimensões de página inválidas/');

        PzPdfPageDimension::custom(0, 300);
    }

    public function testCustomWithZeroHeightThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/Dimensões de página inválidas/');

        PzPdfPageDimension::custom(200, 0);
    }

    public function testCustomWithNegativeWidthThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/Dimensões de página inválidas/');

        PzPdfPageDimension::custom(-10, 300);
    }

    public function testCustomWithNegativeHeightThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/Dimensões de página inválidas/');

        PzPdfPageDimension::custom(200, -10);
    }

    public function testCustomWithBothNegativeThrowsException(): void
    {
        $this->expectException(PzPdfException::class);

        PzPdfPageDimension::custom(-5, -5);
    }

    public function testExceptionMessageContainsDimensions(): void
    {
        try {
            PzPdfPageDimension::custom(0, 300);
            $this->fail('Expected PzPdfException was not thrown');
        } catch (PzPdfException $e) {
            $this->assertStringContainsString('0', $e->getMessage());
            $this->assertStringContainsString('300', $e->getMessage());
            $this->assertStringContainsString('maiores que zero', $e->getMessage());
        }
    }

    public function testIsReadonly(): void
    {
        $dim = PzPdfPageDimension::fromEnum(PzPdfPageSize::A4);

        $reflection = new \ReflectionClass($dim);
        $this->assertTrue($reflection->isReadOnly());
    }
}
