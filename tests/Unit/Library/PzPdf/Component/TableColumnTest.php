<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfTableColumn;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Exception\PzPdfException;

#[CoversClass(PzPdfTableColumn::class)]
final class TableColumnTest extends TestCase
{
    public function test_creation_with_named_args(): void
    {
        $column = new PzPdfTableColumn(label: 'Nome', width: 50, align: PzPdfTextAlignment::Left);

        self::assertSame('Nome', $column->label);
        self::assertSame(50.0, $column->width);
        self::assertSame(PzPdfTextAlignment::Left, $column->align);
    }

    public function test_default_alignment_is_left(): void
    {
        $column = new PzPdfTableColumn(label: 'Nome', width: 50);

        self::assertSame(PzPdfTextAlignment::Left, $column->align);
    }

    public function test_creation_with_center_alignment(): void
    {
        $column = new PzPdfTableColumn(label: 'Status', width: 30, align: PzPdfTextAlignment::Center);

        self::assertSame('Status', $column->label);
        self::assertSame(30.0, $column->width);
        self::assertSame(PzPdfTextAlignment::Center, $column->align);
    }

    public function test_creation_with_right_alignment(): void
    {
        $column = new PzPdfTableColumn(label: 'Valor', width: 40, align: PzPdfTextAlignment::Right);

        self::assertSame('Valor', $column->label);
        self::assertSame(40.0, $column->width);
        self::assertSame(PzPdfTextAlignment::Right, $column->align);
    }

    public function test_creation_with_accented_label(): void
    {
        $column = new PzPdfTableColumn(label: 'Descrição', width: 60);

        self::assertSame('Descrição', $column->label);
    }

    public function test_zero_width_throws_pz_pdf_exception(): void
    {
        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('column width');

        new PzPdfTableColumn(label: 'X', width: 0);
    }

    public function test_negative_width_throws_pz_pdf_exception(): void
    {
        $this->expectException(PzPdfException::class);

        new PzPdfTableColumn(label: 'X', width: -1.5);
    }

    public function test_creation_with_decimal_width(): void
    {
        $column = new PzPdfTableColumn(label: 'Preciso', width: 33.5);

        self::assertSame(33.5, $column->width);
    }
}
