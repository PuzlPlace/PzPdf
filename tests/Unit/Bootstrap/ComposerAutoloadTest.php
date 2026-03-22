<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Internal\BootstrapProbe;

final class ComposerAutoloadTest extends TestCase
{
    public function testProductionNamespaceAutoloadsWithoutManualRequire(): void
    {
        self::assertTrue(class_exists(BootstrapProbe::class));
    }

    public function testBootstrapProbeLabel(): void
    {
        $probe = new BootstrapProbe();

        self::assertSame('pzpdf-bootstrap', $probe->label());
    }
}
