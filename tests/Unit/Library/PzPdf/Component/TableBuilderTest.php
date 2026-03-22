<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfTableBuilder;
use Puzl\PzPdf\Component\PzPdfTableColumn;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\ValueObject\PzPdfColor;

#[CoversClass(PzPdfTableBuilder::class)]
final class TableBuilderTest extends TestCase
{
    private PzPdf $pdf;

    private PzPdfTableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdf = new PzPdf;
        $this->pdf->addPage();
        $this->table = new PzPdfTableBuilder($this->pdf);
    }

    public function test_render_header_without_columns_throws(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('columns()');

        $this->table->renderHeader();
    }

    public function test_fluent_chains_return_same_instance(): void
    {
        $c = new PzPdfTableColumn('A', 50);

        $t = $this->table
            ->columns($c)
            ->headerStyle(bgColor: PzPdfColor::rgb(1, 2, 3))
            ->bodyStyle(fontFamily: 'Helvetica', fontStyle: PzPdfFontStyle::Regular)
            ->separators(true, PzPdfColor::black(), 0.1)
            ->autoPageBreakConfig(true, 15)
            ->cellBorder(1);

        self::assertSame($this->table, $t);
    }

    public function test_end_returns_original_pdf(): void
    {
        $this->table->columns(new PzPdfTableColumn('Col', 40))->renderHeader();

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_add_row_pads_missing_cells(): void
    {
        $this->table
            ->columns(
                new PzPdfTableColumn('A', 30),
                new PzPdfTableColumn('B', 30),
            )
            ->renderHeader()
            ->addRow(['only-first']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_multiple_columns_alignment(): void
    {
        $this->table
            ->columns(
                new PzPdfTableColumn(label: 'Nome', width: 60, align: PzPdfTextAlignment::Left),
                new PzPdfTableColumn(label: 'Valor', width: 30, align: PzPdfTextAlignment::Right),
                new PzPdfTableColumn(label: 'Status', width: 25, align: PzPdfTextAlignment::Center),
            )
            ->renderHeader()
            ->addRow(['Produto', '99,00', 'OK']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_add_rows_iterates(): void
    {
        $this->table
            ->columns(new PzPdfTableColumn('C', 50))
            ->renderHeader()
            ->addRows([['1'], ['2'], ['3']]);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_separators_and_row_fill(): void
    {
        $this->table
            ->columns(new PzPdfTableColumn('C', 50))
            ->separators(true, PzPdfColor::rgb(180, 180, 180), 0.15)
            ->renderHeader()
            ->addRow(['a'], PzPdfColor::rgb(240, 240, 240))
            ->addRow(['b']);

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_draw_separator_standalone(): void
    {
        $this->table
            ->columns(new PzPdfTableColumn('C', 40))
            ->renderHeader()
            ->drawSeparator();

        self::assertSame($this->pdf, $this->table->end());
    }

    public function test_auto_page_break_disabled_allows_tall_content(): void
    {
        $this->table
            ->columns(new PzPdfTableColumn('C', 50))
            ->autoPageBreakConfig(false)
            ->renderHeader();

        for ($i = 0; $i < 5; $i++) {
            $this->table->addRow([(string) $i]);
        }

        self::assertSame($this->pdf, $this->table->end());
    }
}
