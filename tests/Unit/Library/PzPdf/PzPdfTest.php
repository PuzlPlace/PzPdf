<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfListBuilder;
use Puzl\PzPdf\Enum\PzPdfDisplayLayout;
use Puzl\PzPdf\Enum\PzPdfDisplayZoom;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfImageAlign;
use Puzl\PzPdf\Enum\PzPdfImageFit;
use Puzl\PzPdf\Enum\PzPdfImageValign;
use Puzl\PzPdf\Enum\PzPdfLineCap;
use Puzl\PzPdf\Enum\PzPdfLineJoin;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Enum\PzPdfTextDirection;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\ValueObject\PzPdfColor;
use Puzl\PzPdf\ValueObject\PzPdfGradientStop;

/**
 * Porte consolidado de {@see \Tests\Unit\Library\PzPdf\PzPdfTest} da referência `api`.
 * Testes baseados em mock de `PdfAdapterInterface`/`getAdapter()` foram substituídos por
 * asserções sobre a fachada real {@see PzPdf} e sobre bytes PDF válidos (`%PDF`, tamanho mínimo).
 */
#[CoversClass(PzPdf::class)]
final class PzPdfTest extends TestCase
{
    public function testFluentMethodsReturnSameInstance(): void
    {
        $pdf = new PzPdf;

        $chain = $pdf
            ->margins(10, 10, 10)
            ->font('Helvetica', PzPdfFontStyle::Regular, 12)
            ->addPage();

        self::assertSame($pdf, $chain);
    }

    public function testMmToPointsConversionMatchesTypographicDefinition(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->mmToPoints(25.4), 0.001);
        self::assertEqualsWithDelta(25.4, $pdf->pointsToMm(72.0), 0.001);
    }

    public function testPolygonRejectsLessThanThreePoints(): void
    {
        $pdf = new PzPdf;

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('polygon() requer no mínimo 3 pontos');

        $pdf->polygon([[0, 0], [1, 1]], 'D');
    }

    public function testAddTrueTypeFontThrowsWhenTtfPathMissing(): void
    {
        $pdf = new PzPdf;

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessageMatches('/não encontrado ou ilegível/');

        $pdf->addTrueTypeFont(
            'X',
            PzPdfFontStyle::Regular,
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_missing_font_'.uniqid('', true).'.ttf',
        );
    }

    public function testBulletListReturnsListBuilderAndEndReturnsSamePdf(): void
    {
        $pdf = new PzPdf;

        $list = $pdf->bulletList();

        self::assertInstanceOf(PzPdfListBuilder::class, $list);
        self::assertSame($pdf, $list->end());
    }

    public function testMinimalClosedDocumentViaToString(): void
    {
        $binary = (new PzPdf)
            ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4)
            ->font('Helvetica', PzPdfFontStyle::Regular, 11)
            ->cell(40, 8, 'ok', 0, 1, PzPdfTextAlignment::Left, false)
            ->toString();

        self::assertStringStartsWith('%PDF', $binary);
    }

    public function testOnHeaderRunsDuringOutputAndProducesPdf(): void
    {
        $pdf = (new PzPdf)
            ->onHeader(static function (PzPdf $p): void {
                $p->font('Helvetica', PzPdfFontStyle::Regular, 8)
                    ->cell(40, 5, 'HDR', 0, 1, PzPdfTextAlignment::Left, false);
            })
            ->addPage();

        $out = $pdf->output('', PzPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $out);
        self::assertGreaterThan(200, strlen($out));
    }

    public function testOnFooterRunsDuringOutputAndProducesPdf(): void
    {
        $pdf = (new PzPdf)
            ->onFooter(static function (PzPdf $p): void {
                $p->font('Helvetica', PzPdfFontStyle::Regular, 8)
                    ->cell(40, 5, 'FTR', 0, 1, PzPdfTextAlignment::Left, false);
            })
            ->addPage();

        $out = $pdf->output('', PzPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $out);
        self::assertGreaterThan(200, strlen($out));
    }

    /**
     * Exercita a maior parte da superfície da fachada num único fluxo (paridade com a suíte da referência).
     */
    public function testWideFacadeSurfaceProducesValidPdf(): void
    {
        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
        self::assertNotFalse($pngData);

        $pngPath = tempnam(sys_get_temp_dir(), 'pzpdf_png_');
        self::assertNotFalse($pngPath);
        file_put_contents($pngPath, $pngData);

        $attachPath = tempnam(sys_get_temp_dir(), 'pzpdf_att_');
        self::assertNotFalse($attachPath);
        file_put_contents($attachPath, 'attachment-payload');

        try {
            $pdf = (new PzPdf)
                ->protect([], 'u', 'o')
                ->title('T')
                ->author('A')
                ->subject('S')
                ->keywords('k')
                ->creator('C')
                ->displayMode(PzPdfDisplayZoom::Default, PzPdfDisplayLayout::Default)
                ->aliasNbPages()
                ->landscape()
                ->pageSize(PzPdfPageSize::A4)
                ->portrait()
                ->customPzPdfPageSize(100, 120)
                ->margins(12, 14, 12, 20)
                ->leftMargin(11)
                ->topMargin(13)
                ->rightMargin(11)
                ->autoPageBreak(true, 18)
                ->addPage()
                ->font('Helvetica', PzPdfFontStyle::Regular, 10)
                ->setXY(20, 20);
            $pdf->getX();
            $pdf->getY();
            $pdf->getPageWidth();
            $pdf->getPageHeight();
            $pdf->pageNo();

            $linkId = $pdf->addLink();

            $pdf
                ->drawColor(PzPdfColor::rgb(10, 20, 30))
                ->fillColor(PzPdfColor::rgb(240, 240, 240))
                ->textColor(PzPdfColor::rgb(0, 0, 0))
                ->lineWidth(0.2)
                ->lineCap(PzPdfLineCap::Round)
                ->lineJoin(PzPdfLineJoin::Round)
                ->dash(1, 1)
                ->line(10, 10, 40, 15)
                ->undash()
                ->rect(10, 30, 30, 15, 'D')
                ->roundedRect(10, 50, 30, 12, 2, 'D')
                ->circle(25, 75, 4, 'D')
                ->ellipse(25, 85, 6, 4, 0, 'D')
                ->polygon([[10, 95], [30, 95], [20, 105]], 'D')
                ->regularPolygon(25, 115, 5, 5, 0, 'D')
                ->curve(10, 125, 15, 130, 25, 130, 30, 125, 'D')
                ->starPolygon(25, 140, 6, 5, 2, 0, 'D')
                ->pushState()
                ->translate(2, 2)
                ->rotate(5, 20, 150)
                ->scale(1.02, 1.02, 20, 150)
                ->scaleXY(1.01, 20, 150)
                ->skew(1, 0, 20, 150)
                ->mirrorH(30)
                ->mirrorV(160)
                ->popState()
                ->clipRect(10, 165, 50, 20)
                ->cell(40, 6, 'cell', 0, 1, PzPdfTextAlignment::Center, false, $linkId)
                ->multiCell(50, 5, "line1\nline2", 0, PzPdfTextAlignment::Left, false)
                ->write(5, ' write ')
                ->newLine(4)
                ->setX(15)
                ->setY(200);
            $pdf->getStringWidth('abc');
            $pdf->getStringHeight("a\nb", 40, 5);

            $pdf
                ->rotatedText(15, 210, 'rot', 15)
                ->textWithDirection(15, 220, 'dir', PzPdfTextDirection::LeftToRight)
                ->bookmark('B', 0, null)
                ->setLink($linkId, 0, -1)
                ->link(10, 230, 20, 6, 'https://example.test')
                ->opacity(0.9)
                ->fillOpacity(0.85)
                ->strokeOpacity(0.95)
                ->opacity(1.0)
                ->linearGradient(
                    10,
                    240,
                    40,
                    15,
                    [
                        PzPdfGradientStop::at(0.0, PzPdfColor::rgb(255, 0, 0)),
                        PzPdfGradientStop::at(1.0, PzPdfColor::rgb(0, 0, 255)),
                    ],
                )
                ->radialGradient(
                    55,
                    240,
                    35,
                    15,
                    [
                        PzPdfGradientStop::at(0.0, PzPdfColor::rgb(0, 255, 0)),
                        PzPdfGradientStop::at(1.0, PzPdfColor::rgb(0, 0, 0)),
                    ],
                )
                ->svgPath('M 10 10 L 20 10 L 20 20 Z', 'D')
                ->textColumns('col1 col2 col3 words', 2, 4, 80, 40)
                ->barcodeEAN13(10, 10, '123456789012', 10, 0.3)
                ->barcodeCode128(10, 25, 'ABC', 0.4, 8)
                ->qrCode(10, 40, 'q', 12)
                ->circularText(60, 40, 15, 'arc', 'top')
                ->image($pngPath, 120, 10, 10, 10)
                ->imageFit($pngPath, 120, 30, 20, 15, PzPdfImageFit::Fit, PzPdfImageAlign::Center, PzPdfImageValign::Center)
                ->imageScaled($pngPath, 120, 50, 0.5)
                ->rotatedImage($pngPath, 120, 70, 10, 10, 10)
                ->imageFromString($pngData, 120, 85, 8, 8);

            if (extension_loaded('gd')) {
                $im = imagecreatetruecolor(2, 2);
                self::assertNotFalse($im);
                $pdf->imageFromGd($im, 120, 100, 6, 6);
            }

            $pdf
                ->attach($attachPath, 'note.txt', 'desc')
                ->openAttachmentPane()
                ->signatureField(120, 115, 30, 10, 'sig1')
                ->clipCircle(130, 130, 5)
                ->clipEllipse(130, 145, 6, 4);
            $pdf->mmToPoints(10);
            $pdf->pointsToMm(28.35);
            $pdf->cmToPoints(1);
            $pdf->inchesToPoints(1);

            $out = $pdf->output('doc.pdf', PzPdfOutputMode::String);
            self::assertStringStartsWith('%PDF', $out);
            self::assertGreaterThan(800, strlen($out));
        } finally {
            if (is_file($pngPath)) {
                unlink($pngPath);
            }
            if (is_file($attachPath)) {
                unlink($attachPath);
            }
        }
    }

    public function testProtectWithoutAnyPasswordThrowsPzPdfException(): void
    {
        $pdf = new PzPdf;

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('protect() requer ao menos uma senha');

        $pdf->protect([], null, null);
    }
}
