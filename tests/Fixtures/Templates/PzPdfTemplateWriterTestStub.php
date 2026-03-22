<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Fixtures\Templates;

use Puzl\PzPdf\Templates\PzPdfTemplateWriter;
use Puzl\PzPdf\ValueObject\PzPdfColor;

/**
 * Expõe estado protegido do template apenas para testes unitários.
 */
final class PzPdfTemplateWriterTestStub extends PzPdfTemplateWriter
{
    public function brandColorForTest(): PzPdfColor
    {
        return $this->brandColor;
    }
}
