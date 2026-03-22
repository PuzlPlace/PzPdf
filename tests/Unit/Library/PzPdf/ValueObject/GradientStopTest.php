<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\ValueObject\PzPdfCmykColor;
use Puzl\PzPdf\ValueObject\PzPdfColor;
use Puzl\PzPdf\ValueObject\PzPdfGradientStop;
use ReflectionClass;

#[CoversClass(PzPdfGradientStop::class)]
final class GradientStopTest extends TestCase
{
    public function test_constructor_sets_position_and_color(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_at_factory_creates_instance(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = PzPdfGradientStop::at(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_zero_is_valid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.0, $color);

        $this->assertSame(0.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_one_is_valid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(1.0, $color);

        $this->assertSame(1.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_position_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('Invalid gradient stop position');

        new PzPdfGradientStop(-0.1, PzPdfColor::rgb(255, 0, 0));
    }

    public function test_position_above_one_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);

        new PzPdfGradientStop(1.1, PzPdfColor::rgb(255, 0, 0));
    }

    public function test_position_middle_is_valid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.5, $color);

        $this->assertSame(0.5, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function test_is_readonly(): void
    {
        $reflection = new ReflectionClass(PzPdfGradientStop::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_at_returns_gradient_stop_instance(): void
    {
        $stop = PzPdfGradientStop::at(0.5, PzPdfColor::rgb(255, 0, 0));

        $this->assertInstanceOf(PzPdfGradientStop::class, $stop);
    }

    public function test_accepts_cmyk_color(): void
    {
        $cmyk = PzPdfCmykColor::cmyk(10, 20, 30, 40);
        $stop = PzPdfGradientStop::at(0.75, $cmyk);

        $this->assertSame($cmyk, $stop->color);
        $this->assertSame([10, 20, 30, 40], $stop->color->toArray());
    }
}
