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
    public function testConstructorSetsPositionAndColor(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function testAtFactoryCreatesInstance(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = PzPdfGradientStop::at(0.25, $color);

        $this->assertSame(0.25, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function testPositionZeroIsValid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.0, $color);

        $this->assertSame(0.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function testPositionOneIsValid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(1.0, $color);

        $this->assertSame(1.0, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function testPositionBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('Posição de gradient stop inválida');

        new PzPdfGradientStop(-0.1, PzPdfColor::rgb(255, 0, 0));
    }

    public function testPositionAboveOneThrowsException(): void
    {
        $this->expectException(PzPdfException::class);

        new PzPdfGradientStop(1.1, PzPdfColor::rgb(255, 0, 0));
    }

    public function testPositionMiddleIsValid(): void
    {
        $color = PzPdfColor::rgb(255, 0, 0);
        $stop = new PzPdfGradientStop(0.5, $color);

        $this->assertSame(0.5, $stop->position);
        $this->assertSame($color, $stop->color);
    }

    public function testIsReadonly(): void
    {
        $reflection = new ReflectionClass(PzPdfGradientStop::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testAtReturnsGradientStopInstance(): void
    {
        $stop = PzPdfGradientStop::at(0.5, PzPdfColor::rgb(255, 0, 0));

        $this->assertInstanceOf(PzPdfGradientStop::class, $stop);
    }

    public function testAcceptsCmykColor(): void
    {
        $cmyk = PzPdfCmykColor::cmyk(10, 20, 30, 40);
        $stop = PzPdfGradientStop::at(0.75, $cmyk);

        $this->assertSame($cmyk, $stop->color);
        $this->assertSame([10, 20, 30, 40], $stop->color->toArray());
    }
}
