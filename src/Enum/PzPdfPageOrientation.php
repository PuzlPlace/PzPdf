<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Orientação da página do documento PDF.
 *
 * Os backed values correspondem aos parâmetros esperados pela API do fawno/fpdf.
 */
enum PzPdfPageOrientation: string
{
    case Portrait = 'P';
    case Landscape = 'L';
}
