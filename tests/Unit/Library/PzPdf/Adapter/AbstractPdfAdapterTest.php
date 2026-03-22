<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\PzPdf;

/**
 * Porte de {@see \Tests\Unit\Library\PzPdf\Adapter\AbstractPdfAdapterTest} da referência `api`.
 * A classe abstrata `AbstractPdfAdapter` e o contrato `PdfAdapterInterface` não existem neste pacote;
 * o motor é sempre {@see \Puzl\PzPdf\Internal\PzPdfFpdfEngine} atrás da fachada {@see PzPdf}.
 * Estes testes preservam as asserções **observáveis** equivalentes: callbacks header/footer,
 * conversões métricas e geração PDF válida (em vez de “unsupported by default” no adapter stub).
 */
#[CoversClass(PzPdf::class)]
final class AbstractPdfAdapterTest extends TestCase
{
    public function testMmToPointsReturnsCorrectValue(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->mmToPoints(25.4), 0.0001);
    }

    public function testPointsToMmReturnsCorrectValue(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(25.4, $pdf->pointsToMm(72.0), 0.0001);
    }

    public function testCmToPointsReturnsCorrectValue(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->cmToPoints(2.54), 0.0001);
    }

    public function testInchesToPointsReturnsCorrectValue(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->inchesToPoints(1.0), 0.0001);
    }

    public function testHeaderAndFooterCallbacksAreInvokedOnOutput(): void
    {
        $headerRan = false;
        $footerRan = false;

        $pdf = (new PzPdf)
            ->onHeader(static function (PzPdf $p) use (&$headerRan): void {
                $headerRan = true;
                $p->font('Helvetica', PzPdfFontStyle::Regular, 8)
                    ->cell(20, 4, 'H', 0, 1, PzPdfTextAlignment::Left, false);
            })
            ->onFooter(static function (PzPdf $p) use (&$footerRan): void {
                $footerRan = true;
                $p->font('Helvetica', PzPdfFontStyle::Regular, 8)
                    ->cell(20, 4, 'F', 0, 1, PzPdfTextAlignment::Left, false);
            })
            ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4);

        $binary = $pdf->output('', PzPdfOutputMode::String);

        self::assertTrue($headerRan);
        self::assertTrue($footerRan);
        self::assertStringStartsWith('%PDF', $binary);
        self::assertGreaterThan(400, strlen($binary));
    }
}
