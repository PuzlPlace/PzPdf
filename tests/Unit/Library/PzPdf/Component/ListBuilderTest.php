<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfListBuilder;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\PzPdf;

#[CoversClass(PzPdfListBuilder::class)]
final class ListBuilderTest extends TestCase
{
    private PzPdf $pdf;

    private PzPdfListBuilder $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdf = new PzPdf;
        $this->pdf->addPage()->font('Helvetica', PzPdfFontStyle::Regular, 10);
        $this->list = new PzPdfListBuilder($this->pdf);
    }

    public function test_bullet_style_fluent(): void
    {
        $r = $this->list->bulletStyle('-', 4, 6);
        self::assertSame($this->list, $r);
    }

    public function test_line_height_fluent(): void
    {
        self::assertSame($this->list, $this->list->lineHeight(7));
    }

    public function test_sub_list_indent_fluent(): void
    {
        self::assertSame($this->list, $this->list->subListIndent(6));
    }

    public function test_items_renders_without_exception(): void
    {
        $this->list
            ->bulletStyle('•', 5, 8)
            ->lineHeight(6)
            ->items(['A', 'B']);

        self::assertSame($this->pdf, $this->list->end());
    }

    public function test_nested_items(): void
    {
        $this->list->items([
            'Root',
            ['Sub one', 'Sub two'],
            'Last',
        ]);

        self::assertSame($this->pdf, $this->list->end());
    }

    public function test_bullet_list_on_facade_returns_builder(): void
    {
        $pdf = new PzPdf;
        $pdf->addPage();

        $b = $pdf->bulletList();
        self::assertInstanceOf(PzPdfListBuilder::class, $b);
        self::assertSame($pdf, $b->end());
    }
}
