<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Integration\Engine;

use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Internal\PzPdfFpdfEngine;
use Puzl\PzPdf\ValueObject\PzPdfColor;

final class PzPdfEngineGeneratesValidPdfTest extends TestCase
{
    public function testMinimalPdfHasValidHeaderAndEof(): void
    {
        $engine = new PzPdfFpdfEngine();
        $engine->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4);
        $engine->setFont('Helvetica', PzPdfFontStyle::Regular, 12);
        $engine->cell(40, 10, 'PzPdf engine', 0, 1, PzPdfTextAlignment::Left, false);

        $binary = $engine->output('', PzPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $binary);
        self::assertStringContainsString('%%EOF', $binary);
        self::assertGreaterThan(400, strlen($binary));
    }

    public function testRectangleWithFillAlphaCompletesWithMeaningfulSize(): void
    {
        $engine = new PzPdfFpdfEngine();
        $engine->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4);
        $engine->setFillColor(PzPdfColor::rgb(200, 30, 30));
        $engine->setFillAlpha(0.5);
        $engine->rect(20, 30, 80, 40, 'F');
        $engine->setAlpha(1.0);

        $binary = $engine->output('', PzPdfOutputMode::String);

        self::assertStringStartsWith('%PDF', $binary);
        self::assertGreaterThan(800, strlen($binary));
    }
}
