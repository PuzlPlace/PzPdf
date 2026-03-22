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
use Puzl\PzPdf\Internal\PzPdfFpdfEngine;
use Puzl\PzPdf\PzPdf;

/**
 * Porte de {@see \Tests\Unit\Library\PzPdf\Adapter\AbstractPdfAdapterTest} da referência `api`.
 * A classe abstrata `AbstractPdfAdapter` e o contrato `PdfAdapterInterface` não existem neste pacote;
 * o motor é sempre {@see PzPdfFpdfEngine} atrás da fachada {@see PzPdf}.
 * Estes testes preservam as asserções **observáveis** equivalentes: callbacks header/footer,
 * conversões métricas e geração PDF válida (em vez de “unsupported by default” no adapter stub).
 */
#[CoversClass(PzPdf::class)]
final class AbstractPdfAdapterTest extends TestCase
{
    public function test_mm_to_points_returns_correct_value(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->mmToPoints(25.4), 0.0001);
    }

    public function test_points_to_mm_returns_correct_value(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(25.4, $pdf->pointsToMm(72.0), 0.0001);
    }

    public function test_cm_to_points_returns_correct_value(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->cmToPoints(2.54), 0.0001);
    }

    public function test_inches_to_points_returns_correct_value(): void
    {
        $pdf = new PzPdf;

        self::assertEqualsWithDelta(72.0, $pdf->inchesToPoints(1.0), 0.0001);
    }

    public function test_header_and_footer_callbacks_are_invoked_on_output(): void
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
