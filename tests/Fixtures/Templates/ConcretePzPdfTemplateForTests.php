<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Fixtures\Templates;

use Puzl\PzPdf\Templates\PzPdfTemplateWriter;

/**
 * Subclasse mínima para exercitar {@see PzPdfTemplateWriter::fromConfigFile()} nos testes.
 *
 * @internal
 */
final class ConcretePzPdfTemplateForTests extends PzPdfTemplateWriter {}
