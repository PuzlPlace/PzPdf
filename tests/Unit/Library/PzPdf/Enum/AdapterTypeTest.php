<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfAdapterType;
use Puzl\PzPdf\Internal\PzPdfFpdfEngine;
use Puzl\PzPdf\PzPdf;
use ReflectionClass;

/**
 * Porte de {@see \Tests\Unit\Library\PzPdf\Enum\AdapterTypeTest} da referência `api`.
 * O enum {@see AdapterType} foi removido da API pública (motor fixo FPDF). Em vez de casos de enum,
 * validamos que {@code new PzPdf()} sempre produz documentos com engine FPDF isolado.
 */
#[CoversNothing]
final class AdapterTypeTest extends TestCase
{
    public function test_new_pz_pdf_always_backed_by_fpdf_engine(): void
    {
        $pdf = new PzPdf;

        self::assertInstanceOf(PzPdf::class, $pdf);

        $ref = new ReflectionClass($pdf);
        $prop = $ref->getProperty('engine');
        $prop->setAccessible(true);
        $engine = $prop->getValue($pdf);

        self::assertInstanceOf(PzPdfFpdfEngine::class, $engine);
    }

    public function test_no_public_adapter_type_enum_in_package(): void
    {
        self::assertFalse(
            enum_exists(PzPdfAdapterType::class),
            'AdapterType não deve existir como tipo público prefixado PzPdf.',
        );
    }
}
