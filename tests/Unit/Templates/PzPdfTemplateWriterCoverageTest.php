<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Config\PzPdfConfiguration;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\Templates\PzPdfTemplateWriter;
use Puzl\PzPdf\Tests\Fixtures\Templates\ConcretePzPdfTemplateForTests;
use Puzl\PzPdf\Tests\Fixtures\Templates\PzPdfTemplateWriterCoverageStub;

#[CoversClass(PzPdfTemplateWriter::class)]
final class PzPdfTemplateWriterCoverageTest extends TestCase
{
    public function test_drawing_helpers_execute_against_pdf(): void
    {
        $root = dirname(__DIR__, 3);
        $ttf = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($ttf)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_tpl_cov_'.uniqid('', true);
        self::assertTrue(mkdir($cacheDir, 0777, true));

        try {
            $template = new PzPdfTemplateWriterCoverageStub([]);

            $pdf = (new PzPdf)
                ->landscape()
                ->addPage(PzPdfPageOrientation::Landscape, PzPdfPageSize::A4)
                ->addTrueTypeFont('Inter', PzPdfFontStyle::Regular, $ttf, $cacheDir)
                ->addTrueTypeFont('Inter', PzPdfFontStyle::Bold, $ttf, $cacheDir)
                ->font('Inter', PzPdfFontStyle::Regular, 8);

            $template->runDrawingSamples($pdf);

            $binary = $pdf->toString();
            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(1500, strlen($binary));
        } finally {
            $this->deleteTree($cacheDir);
        }
    }

    public function test_resolve_local_asset_path_without_public_base_returns_null(): void
    {
        $tpl = new PzPdfTemplateWriterCoverageStub(
            PzPdfConfiguration::fromArray(['paths' => ['public' => '']]),
        );

        self::assertNull($tpl->resolveLocalForTest('img/x.png'));
    }

    public function test_resolve_local_asset_path_with_base_finds_file(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_pub_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        $path = $dir.DIRECTORY_SEPARATOR.'logo.png';
        file_put_contents($path, 'x');

        try {
            $tpl = new PzPdfTemplateWriterCoverageStub(
                PzPdfConfiguration::fromArray(['paths' => ['public' => $dir]]),
            );

            self::assertSame($path, $tpl->resolveLocalForTest('logo.png'));
            self::assertNull($tpl->resolveLocalForTest('missing.png'));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function test_from_config_file_throws_when_missing(): void
    {
        $this->expectException(PzPdfException::class);
        ConcretePzPdfTemplateForTests::fromConfigFile('/nonexistent/pzpdf.php');
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $f) {
            $path = $f->getPathname();
            $f->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
