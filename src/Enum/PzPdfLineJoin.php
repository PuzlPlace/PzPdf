<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Estilo de junção de linha (line join) para operações de desenho PDF.
 *
 * Backed values correspondem aos nomes aceitos pelo PDFDrawTrait::SetLineStyle.
 */
enum PzPdfLineJoin: string
{
    /** Junção pontiaguda (padrão PDF). */
    case Miter = 'miter';

    /** Junção arredondada. */
    case Round = 'round';

    /** Junção chanfrada. */
    case Bevel = 'bevel';
}
