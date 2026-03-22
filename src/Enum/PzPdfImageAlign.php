<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Alinhamento horizontal de imagem dentro de uma área retangular.
 */
enum PzPdfImageAlign: string
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';
}
