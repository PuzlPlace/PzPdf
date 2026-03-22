<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Component;

use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\Exception\PzPdfException;

/**
 * Coluna de tabela para uso em {@see PzPdfTableBuilder} e {@see PzPdfTableWriter}.
 *
 * Objeto imutável com rótulo, largura em milímetros e alinhamento horizontal.
 * Largura deve ser estritamente positiva.
 *
 * @example new PzPdfTableColumn(label: 'Nome', width: 50, align: PzPdfTextAlignment::Left)
 */
final readonly class PzPdfTableColumn
{
    /**
     * @param  string  $label  Texto do cabeçalho da coluna (UTF-8)
     * @param  float  $width  Largura da coluna em milímetros (&gt; 0)
     * @param  PzPdfTextAlignment  $align  Alinhamento horizontal do texto na coluna
     *
     * @throws PzPdfException Se {@see $width} for menor ou igual a zero
     */
    public function __construct(
        public string $label,
        public float $width,
        public PzPdfTextAlignment $align = PzPdfTextAlignment::Left,
    ) {
        if ($width <= 0.0) {
            throw new PzPdfException(
                'PzPdfTableColumn: a largura da coluna deve ser maior que zero (mm). '
                ."Valor recebido: {$width}."
            );
        }
    }
}
