<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Internal;

use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageAlign;
use Puzl\PzPdf\Enum\PzPdfImageFit;
use Puzl\PzPdf\Enum\PzPdfImageValign;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\Internal\PzPdfFpdfEngine;

final class PzPdfFpdfEngineTest extends TestCase
{
    public function testImageFitRejectsNonPositiveAreaWidth(): void
    {
        $engine = new PzPdfFpdfEngine();

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('Largura da área (imageFit)');

        $engine->imageFit(
            '/nonexistent.png',
            0,
            0,
            -10,
            50,
            PzPdfImageFit::Fit,
            PzPdfImageAlign::Left,
            PzPdfImageValign::Top,
        );
    }
}
