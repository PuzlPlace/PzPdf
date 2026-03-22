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
    public function testCmykCreatesInstanceWithCorrectComponents(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 100, 50, 25);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(50, $color->yellow);
        $this->assertSame(25, $color->black);
    }

    public function testConstructorCreatesInstanceWithCorrectComponents(): void
    {
        $color = new PzPdfCmykColor(10, 20, 30, 40);

        $this->assertSame(10, $color->cyan);
        $this->assertSame(20, $color->magenta);
        $this->assertSame(30, $color->yellow);
        $this->assertSame(40, $color->black);
    }

    public function testImplementsPzPdfColorInterface(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertInstanceOf(PzPdfColorInterface::class, $color);
    }

    public function testToArrayReturnsFourComponents(): void
    {
        $color = PzPdfCmykColor::cmyk(10, 20, 30, 40);

        $this->assertSame([10, 20, 30, 40], $color->toArray());
    }

    public function testToArrayWithAllZeros(): void
    {
        $this->assertSame([0, 0, 0, 0], PzPdfCmykColor::cmyk(0, 0, 0, 0)->toArray());
    }

    public function testToArrayWithAllHundred(): void
    {
        $this->assertSame([100, 100, 100, 100], PzPdfCmykColor::cmyk(100, 100, 100, 100)->toArray());
    }

    public function testBoundaryValuesZeroDoNotThrow(): void
    {
        $color = PzPdfCmykColor::cmyk(0, 0, 0, 0);

        $this->assertSame(0, $color->cyan);
        $this->assertSame(0, $color->magenta);
        $this->assertSame(0, $color->yellow);
        $this->assertSame(0, $color->black);
    }

    public function testBoundaryValuesHundredDoNotThrow(): void
    {
        $color = PzPdfCmykColor::cmyk(100, 100, 100, 100);

        $this->assertSame(100, $color->cyan);
        $this->assertSame(100, $color->magenta);
        $this->assertSame(100, $color->yellow);
        $this->assertSame(100, $color->black);
    }

    public function testCyanAbove100ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'cyan' inválido: 101");

        PzPdfCmykColor::cmyk(101, 0, 0, 0);
    }

    public function testMagentaAbove100ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'magenta' inválido: 200");

        PzPdfCmykColor::cmyk(0, 200, 0, 0);
    }

    public function testYellowAbove100ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'yellow' inválido: 150");

        PzPdfCmykColor::cmyk(0, 0, 150, 0);
    }

    public function testBlackAbove100ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'black' inválido: 999");

        PzPdfCmykColor::cmyk(0, 0, 0, 999);
    }

    public function testCyanBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'cyan' inválido: -1");

        PzPdfCmykColor::cmyk(-1, 0, 0, 0);
    }

    public function testMagentaBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'magenta' inválido: -50");

        PzPdfCmykColor::cmyk(0, -50, 0, 0);
    }

    public function testYellowBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'yellow' inválido: -10");

        PzPdfCmykColor::cmyk(0, 0, -10, 0);
    }

    public function testBlackBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage("Componente CMYK 'black' inválido: -100");

        PzPdfCmykColor::cmyk(0, 0, 0, -100);
    }

    public function testExceptionMessageContainsHowToFix(): void
    {
        try {
            PzPdfCmykColor::cmyk(101, 0, 0, 0);
            $this->fail('Expected PzPdfException was not thrown');
        } catch (PzPdfException $e) {
            $this->assertStringContainsString('entre 0 e 100', $e->getMessage());
        }
    }

    public function testReadonlyPropertiesCannotBeModified(): void
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
    public function testValidCmykCombinations(int $c, int $m, int $y, int $k): void
    {
        $color = PzPdfCmykColor::cmyk($c, $m, $y, $k);

        $this->assertSame([$c, $m, $y, $k], $color->toArray());
    }
}
