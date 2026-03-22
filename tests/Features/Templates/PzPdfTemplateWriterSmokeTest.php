<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Templates;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Config\PzPdfConfiguration;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\PzPdf;
use Puzl\PzPdf\Templates\PzPdfTemplateWriter;
use Puzl\PzPdf\Tests\Fixtures\Templates\ConcretePzPdfTemplateForTests;

#[CoversClass(PzPdfTemplateWriter::class)]
final class PzPdfTemplateWriterSmokeTest extends TestCase
{
    public function testSmokeFromPackageConfigFileWithTtfStub(): void
    {
        $root = dirname(__DIR__, 3);
        $ttf = $root.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($ttf)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $configPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'pzpdf.php';
        self::assertFileExists($configPath);

        /** @var array<string, mixed> $configData */
        $configData = require $configPath;
        $configuration = PzPdfConfiguration::fromArray($configData);

        $cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_tpl_smoke_'.uniqid('', true);
        self::assertTrue(mkdir($cacheDir, 0777, true));

        try {
            $template = new class ($configuration, $ttf, $cacheDir) extends PzPdfTemplateWriter {
                public function __construct(
                    PzPdfConfiguration $configuration,
                    private readonly string $ttfPath,
                    private readonly string $fontCacheDir,
                ) {
                    parent::__construct($configuration);
                }

                protected function registerFonts(PzPdf $pdf): void
                {
                    $pdf->addTrueTypeFont('Inter', PzPdfFontStyle::Regular, $this->ttfPath, $this->fontCacheDir)
                        ->addTrueTypeFont('Inter', PzPdfFontStyle::Bold, $this->ttfPath, $this->fontCacheDir);
                }

                public function build(): string
                {
                    $pdf = $this->initialize('PZPDF_SMOKE_HDR', '');
                    $pdf->title('PZPDF_SMOKE_DOC_TITLE');

                    return $pdf->cell(0, 8, 'PZPDF_SMOKE_BODY', 0, 1)->toString();
                }
            };

            $binary = $template->build();

            self::assertStringStartsWith('%PDF', $binary);
            self::assertGreaterThan(2000, strlen($binary));
            self::assertTrue(
                str_contains($binary, 'PZPDF_SMOKE_DOC_TITLE'),
                'Metadado do documento deve aparecer no stream PDF (texto do corpo pode estar comprimido).'
            );
        } finally {
            $this->deleteTree($cacheDir);
        }
    }

    public function testCarbonFormatsDateAccordingToLocaleConfig(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00', 'UTC'));
        try {
            $writer = new class (PzPdfConfiguration::fromArray([
                'datetime' => [
                    'locale' => 'en',
                    'timezone' => 'UTC',
                ],
            ])) extends PzPdfTemplateWriter {
                public function previewSubtitle(): string
                {
                    $tz = (string) $this->configuration->get('datetime.timezone', 'UTC');
                    $locale = (string) $this->configuration->get('datetime.locale', 'en');

                    return $this->upper(
                        Carbon::now($tz)->locale($locale)->translatedFormat('d M Y'),
                    );
                }
            };

            self::assertSame('10 JAN 2024', $writer->previewSubtitle());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function testStaticFromConfigFileLoadsPackageConfig(): void
    {
        $root = dirname(__DIR__, 3);
        $path = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'pzpdf.php';

        $instance = ConcretePzPdfTemplateForTests::fromConfigFile($path);

        self::assertInstanceOf(PzPdfTemplateWriter::class, $instance);
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
