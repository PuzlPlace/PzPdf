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
    public function test_image_fit_rejects_non_positive_area_width(): void
    {
        $engine = new PzPdfFpdfEngine;

        $this->expectException(PzPdfException::class);
        $this->expectExceptionMessage('Area width (imageFit)');

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
