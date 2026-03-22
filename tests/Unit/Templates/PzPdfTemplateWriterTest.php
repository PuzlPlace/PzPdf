<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Config\PzPdfConfiguration;
use Puzl\PzPdf\Templates\PzPdfTemplateWriter;
use Puzl\PzPdf\Tests\Fixture\Templates\PzPdfTemplateWriterTestStub;

#[CoversClass(PzPdfTemplateWriter::class)]
final class PzPdfTemplateWriterTest extends TestCase
{
    public function testStubAppliesBaseColorsFromConfig(): void
    {
        $config = PzPdfConfiguration::fromArray([
            'colors' => [
                'primary' => ['r' => 10, 'g' => 20, 'b' => 30],
                'text' => ['r' => 1, 'g' => 2, 'b' => 3],
                'group_bg' => ['r' => 4, 'g' => 5, 'b' => 6],
                'border' => ['r' => 7, 'g' => 8, 'b' => 9],
                'row_separator' => ['r' => 11, 'g' => 12, 'b' => 13],
                'text_light' => ['r' => 14, 'g' => 15, 'b' => 16],
            ],
        ]);

        $stub = new PzPdfTemplateWriterTestStub($config);
        $brand = $stub->brandColorForTest();

        self::assertSame(10, $brand->red);
        self::assertSame(20, $brand->green);
        self::assertSame(30, $brand->blue);
    }
}
