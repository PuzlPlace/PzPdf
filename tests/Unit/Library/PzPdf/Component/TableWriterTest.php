<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfTableColumn;
use Puzl\PzPdf\Component\PzPdfTableWriter;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\ValueObject\PzPdfColor;

#[CoversClass(PzPdfTableWriter::class)]
final class TableWriterTest extends TestCase
{
    public function testThrowsWhenWritingHeaderWithoutColumns(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $writer = new PzPdfTableWriter($pdf);

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('columns()');

        $writer->writeHeader();
    }

    public function testMinimumBodyRowHeightForCellsRequiresColumns(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $writer = new PzPdfTableWriter($pdf);

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('columns()');

        $writer->minimumBodyRowHeightForCells(['x']);
    }

    public function testFluentReturnsSelf(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $w = new PzPdfTableWriter($pdf);
        self::assertSame($w, $w->atX(3));
        self::assertSame($w, $w->columns(new PzPdfTableColumn('A', 40)));
        self::assertSame($w, $w->font('Helvetica', 8));
        self::assertSame($w, $w->textColors(PzPdfColor::black(), PzPdfColor::black()));
    }

    public function testFluentContinued(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $w = new PzPdfTableWriter($pdf);
        $w->columns(new PzPdfTableColumn('A', 40));
        self::assertSame($w, $w->rowHeights(6, 5));
        self::assertSame($w, $w->autoPageBreak(true, 12));
        self::assertSame($w, $w->writeHeader());
        self::assertSame($w, $w->writeRow(['x']));
        self::assertSame($w, $w->writeRows([['a'], ['b']]));
    }

    public function testEndReturnsSamePdf(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $out = (new PzPdfTableWriter($pdf))
            ->atX(5)
            ->columns(new PzPdfTableColumn('COL', 50))
            ->writeHeader()
            ->writeRow(['valor'])
            ->end();

        self::assertSame($pdf, $out);
    }

    public function testMinimumBodyRowHeightForWrappedTextIsAtLeastLineHeight(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage()->font('Helvetica', PzPdfFontStyle::Regular, 10);

        $writer = (new PzPdfTableWriter($pdf))
            ->columns(new PzPdfTableColumn('T', 20))
            ->font('Helvetica', 10)
            ->rowHeights(5, 5);

        $short = $writer->minimumBodyRowHeightForCells(['Hi']);
        self::assertGreaterThanOrEqual($writer->getBodyRowHeight(), $short);

        $long = 'word '.str_repeat('x ', 40);
        $tall = $writer->minimumBodyRowHeightForCells([$long]);
        self::assertGreaterThanOrEqual($writer->getBodyRowHeight(), $tall);
        self::assertGreaterThan($writer->getBodyRowHeight(), $tall);
    }
}
