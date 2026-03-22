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
    public function test_rgb_creates_color_with_correct_components(): void
    {
        $color = PzPdfColor::rgb(51, 122, 183);

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function test_constructor_creates_color_with_correct_components(): void
    {
        $color = new PzPdfColor(10, 20, 30);

        $this->assertSame(10, $color->red);
        $this->assertSame(20, $color->green);
        $this->assertSame(30, $color->blue);
    }

    public function test_rgb_boundary_values_zero(): void
    {
        $color = PzPdfColor::rgb(0, 0, 0);

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function test_rgb_boundary_values255(): void
    {
        $color = PzPdfColor::rgb(255, 255, 255);

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function test_hex_with_hash_creates_correct_color(): void
    {
        $color = PzPdfColor::hex('#337ab7');

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function test_hex_without_hash_creates_correct_color(): void
    {
        $color = PzPdfColor::hex('337ab7');

        $this->assertSame(51, $color->red);
        $this->assertSame(122, $color->green);
        $this->assertSame(183, $color->blue);
    }

    public function test_hex_uppercase_creates_correct_color(): void
    {
        $color = PzPdfColor::hex('#FF00AA');

        $this->assertSame(255, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(170, $color->blue);
    }

    public function test_hex_black_creates_correct_color(): void
    {
        $color = PzPdfColor::hex('#000000');

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function test_hex_white_creates_correct_color(): void
    {
        $color = PzPdfColor::hex('#ffffff');

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function test_white_factory_returns_correct_color(): void
    {
        $color = PzPdfColor::white();

        $this->assertSame(255, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(255, $color->blue);
    }

    public function test_black_factory_returns_correct_color(): void
    {
        $color = PzPdfColor::black();

        $this->assertSame(0, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function test_red_above255_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: red value (300) is out of range 0-255.');

        PzPdfColor::rgb(300, 0, 0);
    }

    public function test_green_above255_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: green value (256) is out of range 0-255.');

        PzPdfColor::rgb(0, 256, 0);
    }

    public function test_blue_above255_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: blue value (999) is out of range 0-255.');

        PzPdfColor::rgb(0, 0, 999);
    }

    public function test_red_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: red value (-1) is out of range 0-255.');

        PzPdfColor::rgb(-1, 0, 0);
    }

    public function test_green_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: green value (-50) is out of range 0-255.');

        PzPdfColor::rgb(0, -50, 0);
    }

    public function test_blue_below_zero_throws_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('PzPdfColor: blue value (-100) is out of range 0-255.');

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
    public function test_hex_invalid_format_throws_exception(string $invalidHex): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/PzPdfColor: invalid hexadecimal value/');

        PzPdfColor::hex($invalidHex);
    }

    public function test_hex_invalid_format_exception_contains_expected_format(): void
    {
        try {
            PzPdfColor::hex('xyz');
            $this->fail('Expected PzPdfException was not thrown');
        } catch (PzPdfException $e) {
            $this->assertStringContainsString("'#RRGGBB'", $e->getMessage());
            $this->assertStringContainsString("'RRGGBB'", $e->getMessage());
        }
    }

    public function test_readonly_properties_cannot_be_modified(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->expectException(Error::class);

        /** @phpstan-ignore-next-line Intentionally testing readonly violation */
        $color->red = 50;
    }

    public function test_hex_and_rgb_produce_same_result(): void
    {
        $fromHex = PzPdfColor::hex('#337ab7');
        $fromRgb = PzPdfColor::rgb(51, 122, 183);

        $this->assertSame($fromHex->red, $fromRgb->red);
        $this->assertSame($fromHex->green, $fromRgb->green);
        $this->assertSame($fromHex->blue, $fromRgb->blue);
    }

    public function test_implements_pz_pdf_color_interface(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->assertInstanceOf(PzPdfColorInterface::class, $color);
    }

    public function test_to_array_returns_rgb_components(): void
    {
        $color = PzPdfColor::rgb(100, 150, 200);

        $this->assertSame([100, 150, 200], $color->toArray());
    }

    public function test_to_array_with_black(): void
    {
        $this->assertSame([0, 0, 0], PzPdfColor::rgb(0, 0, 0)->toArray());
    }

    public function test_to_array_with_white(): void
    {
        $this->assertSame([255, 255, 255], PzPdfColor::rgb(255, 255, 255)->toArray());
    }

    public function test_to_array_with_factory_methods(): void
    {
        $this->assertSame([0, 0, 0], PzPdfColor::black()->toArray());
        $this->assertSame([255, 255, 255], PzPdfColor::white()->toArray());
    }

    public function test_to_array_from_hex(): void
    {
        $color = PzPdfColor::hex('#337ab7');

        $this->assertSame([51, 122, 183], $color->toArray());
    }
}
