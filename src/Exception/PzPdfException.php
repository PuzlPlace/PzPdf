<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Exception;

use RuntimeException;

/**
 * Exceção base para todos os erros do PzPdf.
 *
 * Mensagens seguem o padrão: "o que falhou" + "como corrigir".
 */
class PzPdfException extends RuntimeException {}
