<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\PzPdf;

use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\PzPdf;

final class PzPdfGenerationTest extends TestCase
{
    public function test_factory_to_text_to_binary_string(): void
    {
        $pdf = new PzPdf;

        $binary = $pdf
            ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4)
            ->font('Helvetica', PzPdfFontStyle::Regular, 12)
            ->cell(0, 10, 'PzPdf integration', 0, 1, PzPdfTextAlignment::Left, false)
            ->toString();

        self::assertStringStartsWith('%PDF', $binary);
        self::assertStringContainsString('%%EOF', $binary);
        self::assertGreaterThan(200, strlen($binary));
    }

    public function test_save_writes_non_empty_file(): void
    {
        $root = dirname(__DIR__, 3);
        $tmpDir = $root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $path = $tmpDir.DIRECTORY_SEPARATOR.'pzpdf_task4_'.bin2hex(random_bytes(8)).'.pdf';

        try {
            (new PzPdf)
                ->addPage()
                ->font('Helvetica', PzPdfFontStyle::Regular, 10)
                ->cell(50, 8, 'file', 0, 1, PzPdfTextAlignment::Left, false)
                ->save($path);

            self::assertFileExists($path);
            self::assertGreaterThan(0, filesize($path));
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            self::assertStringStartsWith('%PDF', $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
