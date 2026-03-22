<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Service\PzPdfFpdfImageTypeResolver;

#[CoversClass(PzPdfFpdfImageTypeResolver::class)]
final class FpdfImageTypeResolverTest extends TestCase
{
    public function testDetectsPngByMagicBytes(): void
    {
        $path = $this->writeTempImage(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true),
            'png',
        );

        try {
            $r = PzPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(PzPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('png', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function testDetectsJpegByMagicBytes(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('ext-gd necessário para gerar JPEG de teste.');
        }

        $im = imagecreatetruecolor(2, 2);
        self::assertNotFalse($im);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_img_'.uniqid('', true).'.jpg';
        self::assertTrue(imagejpeg($im, $path));
        imagedestroy($im);

        try {
            $r = PzPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(PzPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('jpg', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function testDetectsGifByMagicBytes(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true);
        self::assertNotFalse($gif);
        $path = $this->writeTempImage($gif, 'gif');

        try {
            $r = PzPdfFpdfImageTypeResolver::resolve($path);
            self::assertSame(PzPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            self::assertSame('gif', $r['type']);
        } finally {
            unlink($path);
        }
    }

    public function testMissingFileReturnsUnknownType(): void
    {
        $r = PzPdfFpdfImageTypeResolver::resolve('/no/such/image_'.uniqid().'.png');
        self::assertSame(PzPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
        self::assertSame('', $r['type']);
    }

    public function testWebpFallsBackToGdWhenRasterSupported(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            self::markTestSkipped('ext-gd com imagewebp necessário.');
        }

        $im = imagecreatetruecolor(2, 2);
        self::assertNotFalse($im);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_webp_'.uniqid('', true).'.webp';
        self::assertTrue(imagewebp($im, $path));
        imagedestroy($im);

        try {
            $r = PzPdfFpdfImageTypeResolver::resolve($path);
            if ($r['mode'] === PzPdfFpdfImageTypeResolver::MODE_GD) {
                self::assertArrayHasKey('gd', $r);
                imagedestroy($r['gd']);
            } else {
                self::assertSame(PzPdfFpdfImageTypeResolver::MODE_FILE, $r['mode']);
            }
        } finally {
            unlink($path);
        }
    }

    /**
     * @param  non-empty-string  $suffix
     */
    private function writeTempImage(string $binary, string $suffix): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_img_'.uniqid('', true).'.'.$suffix;
        file_put_contents($path, $binary);

        return $path;
    }
}
