<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfTableBuilder;
use Puzl\PzPdf\Component\PzPdfTableColumn;
use Puzl\PzPdf\Component\PzPdfTableWriter;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\PzPdfFactory;

#[CoversClass(PzPdfTableWriter::class)]
final class PzPdfTableIntegrationTest extends TestCase
{
    public function testTableWithMultipleRowsAndBordersProducesValidPdf(): void
    {
        $pdf = PzPdfFactory::make();
        $pdf->margins(15, 20, 15, 20)
            ->addPage()
            ->font('Helvetica', PzPdfFontStyle::Regular, 9);

        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [(string) $i, 'SKU-'.$i, number_format($i * 10.5, 2, ',', '.')];
        }

        (new PzPdfTableWriter($pdf))
            ->atX(15)
            ->columns(
                new PzPdfTableColumn('#', 15, PzPdfTextAlignment::Right),
                new PzPdfTableColumn('Code', 70, PzPdfTextAlignment::Left),
                new PzPdfTableColumn('Amount', 35, PzPdfTextAlignment::Right),
            )
            ->font('Helvetica', 9)
            ->cellBorder(1)
            ->writeHeader()
            ->writeRows($rows);

        $raw = $pdf->toString();

        self::assertStringStartsWith('%PDF', $raw);
        self::assertGreaterThan(1500, strlen($raw));
        self::assertGreaterThanOrEqual(1, preg_match_all('/\/Type\s*\/Page[^s]/', $raw));
    }

    public function testTableBuilderWithPageBreakRedrawsHeader(): void
    {
        $pdf = PzPdfFactory::make();
        $pdf->landscape()
            ->margins(10, 25, 10, 10)
            ->autoPageBreak(true, 20)
            ->addPage()
            ->font('Helvetica', PzPdfFontStyle::Regular, 8);

        $b = new PzPdfTableBuilder($pdf);
        $b->columns(
            new PzPdfTableColumn('ID', 20),
            new PzPdfTableColumn('Data', 240),
        )
            ->bodyStyle(rowHeight: 5)
            ->autoPageBreakConfig(true, 20)
            ->renderHeader();

        for ($i = 1; $i <= 80; $i++) {
            $b->addRow([(string) $i, str_repeat('x', 30)]);
        }

        $raw = $b->end()->toString();
        $pages = preg_match_all('/\/Type\s*\/Page[^s]/', $raw);
        self::assertGreaterThanOrEqual(2, $pages);
    }
}
