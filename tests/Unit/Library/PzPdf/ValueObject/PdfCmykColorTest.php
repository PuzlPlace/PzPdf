<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\ValueObject;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\ValueObject\PzPdfCmykColor;

#[CoversClass(PzPdfCmykColor::class)]
final class PdfCmykColorTest extends TestCase
{
    public function test_cmyk_creates_instance_with_correct_components(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 100, 50, 25);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(50, $color->yellow);
        $this->assertSame(25, $color->black);
    }

    public function test_constructor_creates_instance_with_correct_components(): void
    {
        $color = new PzPdfCmykColor(10, 20, 30, 40);

        $this->assertSame(10, $color->cyan);
        $this->assertSame(20, $color->magenta);
        $this->assertSame(30, $color->yellow);
        $this->assertSame(40, $color->black);
    }

    public function test_implements_pz_pdf_color_interface(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertInstanceOf(PzPdfColorInterface::class, $color);
    }

    public function test_to_array_returns_four_components(): void
    {
        $color = PzPdfCmykColor::cmyk(10, 20, 30, 40);

        $this->assertSame([10, 20, 30, 40], $color->toArray());
    }

    public function test_to_array_with_all_zeros(): void
    {
        $this->assertSame([0, 0, 0, 0], PzPdfCmykColor::cmyk(0, 0, 0, 0)->toArray());
    }

    public function test_to_array_with_all_hundred(): void
    {
        $this->assertSame([100, 100, 100, 100], PzPdfCmykColor::cmyk(100, 100, 100, 100)->toArray());
    }

    public function test_boundary_values_zero_do_not_throw(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(0, $color->magenta);
        $this->assertSame(0, $color->yellow);
        $this->assertSame(0, $color->black);
    }

    public function test_boundary_values_hundred_do_not_throw(): void
    {
        $color = PzPdfCmykColor::cmyk(100, 100, 100, 100);

        $this->assertSame(100, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(100, $color->yellow);
        $this->assertSame(100, $color->black);
    }

    public function test_cyan_above100_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'cyan': 101");

        PzPdfCmykColor::cmyk(101, 0, 0, 0);
    }

    public function test_magenta_above100_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'magenta': 200");

        PzPdfCmykColor::cmyk(0, 200, 0, 0);
    }

    public function test_yellow_above100_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'yellow': 150");

        PzPdfCmykColor::cmyk(0, 0, 150, 0);
    }

    public function test_black_above100_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'black': 999");

        PzPdfCmykColor::cmyk(0, 0, 0, 999);
    }

    public function test_cyan_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'cyan': -1");

        PzPdfCmykColor::cmyk(-1, 0, 0, 0);
    }

    public function test_magenta_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'magenta': -50");

        PzPdfCmykColor::cmyk(0, -50, 0, 0);
    }

    public function test_yellow_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'yellow': -10");

        PzPdfCmykColor::cmyk(0, 0, -10, 0);
    }

    public function test_black_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Invalid CMYK component 'black': -100");

        PzPdfCmykColor::cmyk(0, 0, 0, -100);
    }

    public function test_exception_message_contains_how_to_fix(): void
    {
        try {
            PzPdfCmykColor::cmyk(101, 0, 0, 0);
            $this->fail('Expected PzPdfException was not thrown');
        } catch (PzPdfException $e) {
            $this->assertStringContainsString('between 0 and 100', $e->getMessage());
        }
    }

    public function test_readonly_properties_cannot_be_modified(): void
    {
        $color = PzPdfCmykColor::cmyk(50, 50, 50, 50);

        $this->expectException(Error::class);

        /** @phpstan-ignore-next-line Intentionally testing readonly violation */
        $color->cyan = 10;
    }

    /**
     * @return array<string, array{int, int, int, int}>
     */
    public static function validCmykProvider(): array
    {
        return [
            'pure cyan' => [100, 0, 0, 0],
            'pure magenta' => [0, 100, 0, 0],
            'pure yellow' => [0, 0, 100, 0],
            'pure black' => [0, 0, 0, 100],
            'rich black' => [60, 40, 40, 100],
            'mid values' => [50, 50, 50, 50],
        ];
    }

    #[DataProvider('validCmykProvider')]
    public function test_valid_cmyk_combinations(int $c, int $m, int $y, int $k): void
    {
        $color = PzPdfCmykColor::cmyk($c, $m, $y, $k);

        $this->assertSame([$c, $m, $y, $k], $color->toArray());
    }
}
