<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Layout de exibição do viewer PDF ao abrir o documento.
 *
 * Os backed values correspondem aos parâmetros aceitos pelo FPDF::SetDisplayMode().
 */
enum PzPdfDisplayLayout: string
{
    case Single = 'single';
    case Continuous = 'continuous';
    case Two = 'two';
    case Default = 'default';
}
