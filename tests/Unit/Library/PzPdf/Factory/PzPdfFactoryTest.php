<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Internal\PzPdfFpdfEngine;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\PzPdfFactory;
use ReflectionClass;

/**
 * Porte de {@see \Tests\Unit\Library\PzPdf\Factory\PzPdfFactoryTest} da referência `api`.
 * `AdapterType` e `getAdapter()` foram removidos da API pública; isolamento é validado via engine interno.
 */
#[CoversClass(PzPdfFactory::class)]
final class PzPdfFactoryTest extends TestCase
{
    public function testMakeReturnsPzPdfInstance(): void
    {
        $pdf = PzPdfFactory::make();

        self::assertInstanceOf(PzPdf::class, $pdf);
    }

    public function testConsecutiveMakeCallsYieldDistinctEngineInstances(): void
    {
        $a = PzPdfFactory::make();
        $b = PzPdfFactory::make();

        $engineA = $this->extractEngine($a);
        $engineB = $this->extractEngine($b);

        self::assertNotSame($engineA, $engineB);
    }

    public function testOperationsOnFirstInstanceDoNotAffectSecondInstancePageCount(): void
    {
        $first = PzPdfFactory::make();
        $second = PzPdfFactory::make();

        $first->addPage();

        self::assertSame(0, $second->pageNo());
    }

    private function extractEngine(PzPdf $pdf): PzPdfFpdfEngine
    {
        $ref = new ReflectionClass($pdf);
        $prop = $ref->getProperty('engine');
        $prop->setAccessible(true);
        $engine = $prop->getValue($pdf);

        self::assertInstanceOf(PzPdfFpdfEngine::class, $engine);

        return $engine;
    }
}
