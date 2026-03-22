<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Enum;

/**
 * Modo de saída do documento PDF gerado.
 *
 * - Inline: envia ao browser para exibição inline
 * - Download: força download pelo browser
 * - File: salva em arquivo no servidor
 * - String: retorna o conteúdo como string
 */
enum PzPdfOutputMode: string
{
    case Inline = 'I';
    case Download = 'D';
    case File = 'F';
    case String = 'S';
}
