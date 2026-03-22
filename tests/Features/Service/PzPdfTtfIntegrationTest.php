<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Service;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\PzPdf;

#[CoversNothing]
final class PzPdfTtfIntegrationTest extends TestCase
{
    public function testPdfWithTrueTypeFontRenders(): void
    {
        $root = dirname(__DIR__, 3);
        $font = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture tests/Fixtures/fonts/DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_integ_ttf_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $binary = (new PzPdf)
                ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4)
                ->addTrueTypeFont('DejaVuInteg', PzPdfFontStyle::Regular, $font, $cache)
                ->font('DejaVuInteg', PzPdfFontStyle::Regular, 12)
                ->cell(0, 8, 'TTF UTF-8: áéí óú', 0, 1, PzPdfTextAlignment::Left, false)
                ->toString();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(4000, strlen($binary), 'PDF com TTF deve ter tamanho substancial.');
        } finally {
            $this->deleteTree($cache);
        }
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $f) {
            $path = $f->getPathname();
            $f->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
