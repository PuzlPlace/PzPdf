<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Alinhamento vertical de imagem dentro de uma área retangular.
 */
enum PzPdfImageValign: string
{
    case Top = 'top';
    case Center = 'center';
    case Bottom = 'bottom';
}
