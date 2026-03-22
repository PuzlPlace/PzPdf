<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Service;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\PzPdf;

#[CoversNothing]
final class PzPdfImageIntegrationTest extends TestCase
{
    public function test_pdf_with_embedded_raster_image(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        self::assertNotFalse($png);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_integ_img_'.uniqid('', true).'.png';
        file_put_contents($path, $png);

        try {
            $binary = (new PzPdf)
                ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4)
                ->font('Helvetica', PzPdfFontStyle::Regular, 10)
                ->image($path, 20, 30, 15, 15)
                ->toString();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(1500, strlen($binary), 'PDF com imagem embutida deve exceder limiar mínimo.');
        } finally {
            unlink($path);
        }
    }
}
