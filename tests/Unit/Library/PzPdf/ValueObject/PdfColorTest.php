<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\ValueObject;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Contract\PzPdfColorInterface;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\ValueObject\PzPdfColor;

#[CoversClass(PzPdfColor::class)]
final class PdfColorTest extends TestCase
{
    public function testRgbCreatesColorWithCorrectComponents(): void
    {
        $color = PzPdfColor::rgb(51, 122, 183);

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function testConstructorCreatesColorWithCorrectComponents(): void
    {
        $color = new PzPdfColor(10, 20, 30);

        $this->assertSame(10, $color->red);
        $this->assertSame(20, $color->green);
        $this->assertSame(30, $color->blue);
    }

    public function testRgbBoundaryValuesZero(): void
    {
        $color = PzPdfColor::rgb(0, 0, 0);

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function testRgbBoundaryValues255(): void
    {
        $color = PzPdfColor::rgb(255, 255, 255);

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function testHexWithHashCreatesCorrectColor(): void
    {
        $color = PzPdfColor::hex('#337ab7');

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function testHexWithoutHashCreatesCorrectColor(): void
    {
        $color = PzPdfColor::hex('337ab7');

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function testHexUppercaseCreatesCorrectColor(): void
    {
        $color = PzPdfColor::hex('#FF00AA');

        $this->assertSame(255, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(170, $color->blue);
    }

    public function testHexBlackCreatesCorrectColor(): void
    {
        $color = PzPdfColor::hex('#000000');

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function testHexWhiteCreatesCorrectColor(): void
    {
        $color = PzPdfColor::hex('#ffffff');

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function testWhiteFactoryReturnsCorrectColor(): void
    {
        $color = PzPdfColor::white();

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function testBlackFactoryReturnsCorrectColor(): void
    {
        $color = PzPdfColor::black();

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function testRedAbove255ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de red (300) fora do range 0-255.');

        PzPdfColor::rgb(300, 0, 0);
    }

    public function testGreenAbove255ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de green (256) fora do range 0-255.');

        PzPdfColor::rgb(0, 256, 0);
    }

    public function testBlueAbove255ThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de blue (999) fora do range 0-255.');

        PzPdfColor::rgb(0, 0, 999);
    }

    public function testRedBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de red (-1) fora do range 0-255.');

        PzPdfColor::rgb(-1, 0, 0);
    }

    public function testGreenBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de green (-50) fora do range 0-255.');

        PzPdfColor::rgb(0, -50, 0);
    }

    public function testBlueBelowZeroThrowsException(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: valor de blue (-100) fora do range 0-255.');

        PzPdfColor::rgb(0, 0, -100);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidHexProvider(): array
    {
        return [
            'too short' => ['abc'],
            'too long' => ['1234567'],
            'non-hex chars' => ['xyz123'],
            'all non-hex' => ['ghijkl'],
            'empty string' => [''],
            'only hash' => ['#'],
            'hash with short' => ['#abc'],
            'spaces' => ['33 7a b7'],
        ];
    }

    #[DataProvider('invalidHexProvider')]
    public function testHexInvalidFormatThrowsException(string $invalidHex): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/PzPdfColor: valor hexadecimal .* inválido/');

        PzPdfColor::hex($invalidHex);
    }

    public function testHexInvalidFormatExceptionContainsExpectedFormat(): void
    {
        try {
            PzPdfColor::hex('xyz');
            $this->fail('Expected PzPdfException was not thrown');
        } catch (PzPdfException $e) {
            $this->assertStringContainsString("'#RRGGBB'", $e->getMessage());
            $this->assertStringContainsString("'RRGGBB'", $e->getMessage());
        }
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->expectException(Error::class);

        /** @phpstan-ignore-next-line Intentionally testing readonly violation */
        $color->red = 50;
    }

    public function testHexAndRgbProduceSameResult(): void
    {
        $fromHex = PzPdfColor::hex('#337ab7');
        $fromRgb = PzPdfColor::rgb(51, 122, 183);

        $this->assertSame($fromHex->red, $fromRgb->red);
        $this->assertSame($fromHex->green, $fromRgb->green);
        $this->assertSame($fromHex->blue, $fromRgb->blue);
    }

    public function testImplementsPzPdfColorInterface(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->assertInstanceOf(PzPdfColorInterface::class, $color);
    }

    public function testToArrayReturnsRgbComponents(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->assertSame([100, 150, 200], $color->toArray());
    }

    public function testToArrayWithBlack(): void
    {
        $this->assertSame([0, 0, 0], PzPdfColor::rgb(0, 0, 0)->toArray());
    }

    public function testToArrayWithWhite(): void
    {
        $this->assertSame([255, 255, 255], PzPdfColor::rgb(255, 255, 255)->toArray());
    }

    public function testToArrayWithFactoryMethods(): void
    {
        $this->assertSame([0, 0, 0], PzPdfColor::black()->toArray());
        $this->assertSame([255, 255, 255], PzPdfColor::white()->toArray());
    }

    public function testToArrayFromHex(): void
    {
        $color = PzPdfColor::hex('#337ab7');

        $this->assertSame([51, 122, 183], $color->toArray());
    }
}
