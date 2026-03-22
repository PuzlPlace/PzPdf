<?php

declare(strict_types=1);

namespace Puzl\PzPdf\ValueObject;

/**
 * Caminho do diretório de cache onde o FPDF encontra font.php + font.z gerados a partir do TTF.
 */
final class PzPdfResolvedTtfFont
{
    /**
     * @param  string  $cacheDirectory  Diretório absoluto (sem barra final) com os artefatos FPDF
     * @param  string  $definitionFileName  Sempre font.php após conversão interna padronizada
     */
    public function __construct(
        public readonly string $cacheDirectory,
        public readonly string $definitionFileName = 'font.php',
    ) {}
}
