<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Component;

use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\ValueObject\PzPdfColor;

/**
 * Construção declarativa de tabelas em PDF (cabeçalho preenchido, corpo, separadores opcionais).
 *
 * Opera apenas sobre a API pública de {@see PzPdf}. Com auto page break, adiciona página
 * e redesenha o cabeçalho quando a próxima linha não couber.
 *
 * @example
 * $table = new PzPdfTableBuilder($pdf);
 * $table->columns(
 *     new PzPdfTableColumn(label: 'Nome', width: 60, align: PzPdfTextAlignment::Left),
 *     new PzPdfTableColumn(label: 'Valor', width: 30, align: PzPdfTextAlignment::Right),
 * )
 * ->headerStyle(bgColor: PzPdfColor::rgb(51, 122, 183), textColor: PzPdfColor::white())
 * ->renderHeader()
 * ->addRows($data)
 * ->end();
 */
final class PzPdfTableBuilder
{
    /** @var list<PzPdfTableColumn> */
    private array $columns = [];

    private PzPdfColor $headerBgColor;

    private PzPdfColor $headerTextColor;

    private string $headerFontFamily;

    private PzPdfFontStyle $headerFontStyle;

    private float $headerFontSize;

    private float $headerHeight;

    private string $bodyFontFamily;

    private PzPdfFontStyle $bodyFontStyle;

    private float $bodyFontSize;

    private float $rowHeight;

    private bool $drawSeparators;

    private PzPdfColor $separatorColor;

    private float $separatorWidth;

    private bool $autoPageBreak;

    private float $pageBreakMargin;

    /** Borda FPDF passada a {@see PzPdf::cell()} (0 = sem, 1 = contorno). */
    private int $cellBorder = 0;

    public function __construct(
        private readonly PzPdf $pdf,
    ) {
        $this->headerBgColor = PzPdfColor::rgb(51, 122, 183);
        $this->headerTextColor = PzPdfColor::white();
        $this->headerFontFamily = 'Helvetica';
        $this->headerFontStyle = PzPdfFontStyle::Bold;
        $this->headerFontSize = 9;
        $this->headerHeight = 7;

        $this->bodyFontFamily = 'Helvetica';
        $this->bodyFontStyle = PzPdfFontStyle::Regular;
        $this->bodyFontSize = 9;
        $this->rowHeight = 6;

        $this->drawSeparators = false;
        $this->separatorColor = PzPdfColor::rgb(200, 200, 200);
        $this->separatorWidth = 0.2;

        $this->autoPageBreak = true;
        $this->pageBreakMargin = 20;
    }

    /**
     * Define as colunas da tabela.
     *
     * @return $this
     */
    public function columns(PzPdfTableColumn ...$columns): self
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Estilo visual do cabeçalho; parâmetros null preservam o valor atual.
     *
     * @return $this
     */
    public function headerStyle(
        ?PzPdfColor $bgColor = null,
        ?PzPdfColor $textColor = null,
        ?string $fontFamily = null,
        ?PzPdfFontStyle $fontStyle = null,
        ?float $fontSize = null,
        ?float $height = null,
    ): self {
        if ($bgColor !== null) {
            $this->headerBgColor = $bgColor;
        }
        if ($textColor !== null) {
            $this->headerTextColor = $textColor;
        }
        if ($fontFamily !== null) {
            $this->headerFontFamily = $fontFamily;
        }
        if ($fontStyle !== null) {
            $this->headerFontStyle = $fontStyle;
        }
        if ($fontSize !== null) {
            $this->headerFontSize = $fontSize;
        }
        if ($height !== null) {
            $this->headerHeight = $height;
        }

        return $this;
    }

    /**
     * Estilo visual do corpo; parâmetros null preservam o valor atual.
     *
     * @return $this
     */
    public function bodyStyle(
        ?string $fontFamily = null,
        ?PzPdfFontStyle $fontStyle = null,
        ?float $fontSize = null,
        ?float $rowHeight = null,
    ): self {
        if ($fontFamily !== null) {
            $this->bodyFontFamily = $fontFamily;
        }
        if ($fontStyle !== null) {
            $this->bodyFontStyle = $fontStyle;
        }
        if ($fontSize !== null) {
            $this->bodyFontSize = $fontSize;
        }
        if ($rowHeight !== null) {
            $this->rowHeight = $rowHeight;
        }

        return $this;
    }

    /**
     * Linhas separadoras entre linhas de dados.
     *
     * @return $this
     */
    public function separators(
        bool $enabled = true,
        ?PzPdfColor $color = null,
        ?float $width = null,
    ): self {
        $this->drawSeparators = $enabled;

        if ($color !== null) {
            $this->separatorColor = $color;
        }
        if ($width !== null) {
            $this->separatorWidth = $width;
        }

        return $this;
    }

    /**
     * Quebra de página automática ao adicionar linhas; redesenha o cabeçalho na nova página.
     *
     * @return $this
     */
    public function autoPageBreakConfig(bool $enabled = true, float $margin = 20): self
    {
        $this->autoPageBreak = $enabled;
        $this->pageBreakMargin = $margin;

        return $this;
    }

    /**
     * Define a borda das células (mesma convenção de {@see PzPdf::cell()}).
     *
     * @return $this
     */
    public function cellBorder(int $border): self
    {
        $this->cellBorder = $border;

        return $this;
    }

    /**
     * Renderiza o cabeçalho na posição atual do cursor.
     *
     * @return $this
     *
     * @throws PzPdfException Se nenhuma coluna foi definida
     */
    public function renderHeader(): self
    {
        $this->assertHasColumns();

        $this->pdf
            ->font($this->headerFontFamily, $this->headerFontStyle, $this->headerFontSize)
            ->fillColor($this->headerBgColor)
            ->textColor($this->headerTextColor);

        foreach ($this->columns as $column) {
            $this->pdf->cell(
                $column->width,
                $this->headerHeight,
                $column->label,
                $this->cellBorder,
                0,
                $column->align,
                true,
            );
        }

        $this->pdf->newLine($this->headerHeight);

        return $this;
    }

    /**
     * Uma linha de dados (valores na ordem das colunas).
     *
     * @param  array<int, string>  $data
     * @return $this
     */
    public function addRow(array $data, ?PzPdfColor $bgColor = null): self
    {
        $this->assertHasColumns();

        $this->checkPageBreak();

        $this->pdf->font($this->bodyFontFamily, $this->bodyFontStyle, $this->bodyFontSize);

        $fill = $bgColor !== null;
        if ($fill) {
            $this->pdf->fillColor($bgColor);
        }

        $this->pdf->textColor(PzPdfColor::black());

        $columnCount = count($this->columns);
        for ($i = 0; $i < $columnCount; $i++) {
            $column = $this->columns[$i];
            $text = $data[$i] ?? '';

            $this->pdf->cell(
                $column->width,
                $this->rowHeight,
                $text,
                $this->cellBorder,
                0,
                $column->align,
                $fill,
            );
        }

        $this->pdf->newLine($this->rowHeight);

        if ($this->drawSeparators) {
            $this->drawSeparator();
        }

        return $this;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return $this
     */
    public function addRows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * Desenha uma linha separadora sob o cursor, com a largura total das colunas.
     *
     * @return $this
     */
    public function drawSeparator(): self
    {
        $totalWidth = $this->calculateTotalWidth();
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();

        $this->pdf
            ->drawColor($this->separatorColor)
            ->lineWidth($this->separatorWidth)
            ->line($x, $y, $x + $totalWidth, $y);

        return $this;
    }

    public function end(): PzPdf
    {
        return $this->pdf;
    }

    private function assertHasColumns(): void
    {
        if ($this->columns === []) {
            throw new PzPdfException(
                'PzPdfTableBuilder: defina as colunas com columns() antes de renderizar ou adicionar linhas.'
            );
        }
    }

    private function checkPageBreak(): void
    {
        if (! $this->autoPageBreak) {
            return;
        }

        $currentY = $this->pdf->getY();
        $pageHeight = $this->pdf->getPageHeight();
        $availableSpace = $pageHeight - $this->pageBreakMargin;

        if ($currentY + $this->rowHeight > $availableSpace) {
            $this->pdf->addPage();
            $this->renderHeader();
        }
    }

    private function calculateTotalWidth(): float
    {
        $total = 0.0;
        foreach ($this->columns as $column) {
            $total += $column->width;
        }

        return $total;
    }
}
