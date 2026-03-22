<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Contract;

/**
 * Contrato para cores usadas em operações PDF (RGB ou CMYK).
 *
 * Implementado por {@see \Puzl\PzPdf\ValueObject\PzPdfColor} e
 * {@see \Puzl\PzPdf\ValueObject\PzPdfCmykColor}.
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
