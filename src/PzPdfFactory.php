<?php

declare(strict_types=1);

namespace Puzl\PzPdf;

/**
 * Ponto de entrada oficial para criar documentos PDF com a API {@see PzPdf}.
 *
 * Cada chamada a {@see self::make()} retorna uma nova instância com motor FPDF dedicado.
 * Não há singleton nem cache mutável compartilhado entre instâncias — adequado para jobs
 * concorrentes, filas e requisições HTTP onde cada geração deve ser isolada.
 */
final class PzPdfFactory
{
    /**
     * Cria um documento PDF novo, com estado e motor internos exclusivos desta instância.
     */
    public static function make(): PzPdf
    {
        return new PzPdf;
    }

    /**
     * Impede instanciação direta; use apenas {@see self::make()}.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
