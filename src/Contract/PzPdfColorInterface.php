<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Contract;

use Puzl\PzPdf\ValueObject\PzPdfCmykColor;
use Puzl\PzPdf\ValueObject\PzPdfColor;

/**
 * Contrato para cores usadas em operações PDF (RGB ou CMYK).
 *
 * Implementado por {@see PzPdfColor} e
 * {@see PzPdfCmykColor}.
 */
interface PzPdfColorInterface
{
    /**
     * RGB retorna [r, g, b] (0–255). CMYK retorna [c, m, y, k] (0–100).
     *
     * @return array<int, int>
     */
    public function toArray(): array;
}
