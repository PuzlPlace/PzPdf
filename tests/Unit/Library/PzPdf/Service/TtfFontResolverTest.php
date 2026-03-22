<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\Service\PzPdfTtfFontResolver;
use Puzl\PzPdf\ValueObject\PzPdfResolvedTtfFont;

#[CoversClass(PzPdfTtfFontResolver::class)]
final class TtfFontResolverTest extends TestCase
{
    public function testRejectsMissingFontFile(): void
    {
        $resolver = new PzPdfTtfFontResolver;
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_unit_cache_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessage('não encontrado ou ilegível');

            $resolver->ensureFpdfFontFiles(
                sys_get_temp_dir().DIRECTORY_SEPARATOR.'missing_font_'.uniqid('', true).'.ttf',
                $cache,
            );
        } finally {
            rmdir($cache);
        }
    }

    public function testRejectsUnsupportedExtension(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pzpdf_font_txt_');
        self::assertNotFalse($path);
        file_put_contents($path, 'not a font');

        $resolver = new PzPdfTtfFontResolver;
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_unit_cache2_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessage('Extensão não suportada');

            $resolver->ensureFpdfFontFiles($path, $cache);
        } finally {
            unlink($path);
            rmdir($cache);
        }
    }

    public function testThrowsWhenInjectedMakefontScriptMissing(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_mkfont_bad_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $badScript = sys_get_temp_dir().DIRECTORY_SEPARATOR.'not_makefont_'.uniqid('', true).'.php';
        self::assertFileDoesNotExist($badScript);

        $resolver = new PzPdfTtfFontResolver($badScript);

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessageMatches('/makefont inválido/');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function testThrowsWhenMakefontProducesTinyFontPhp(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $scriptPhp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_mf_tinyphp_'.uniqid('', true).'.php';
        file_put_contents(
            $scriptPhp,
            <<<'PHP'
<?php
declare(strict_types=1);
$cwd = getcwd();
if ($cwd === false) {
    fwrite(STDERR, 'no cwd');
    exit(1);
}
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.php', 'short');
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.z', 'z');
exit(0);
PHP
        );

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_tinyphp_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new PzPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessage('font.php esperado');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function testThrowsWhenMakefontProducesEmptyFontZ(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $scriptPhp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_mf_emptyz_'.uniqid('', true).'.php';
        file_put_contents(
            $scriptPhp,
            <<<'PHP'
<?php
declare(strict_types=1);
$cwd = getcwd();
if ($cwd === false) {
    fwrite(STDERR, 'no cwd');
    exit(1);
}
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.php', str_repeat('x', 50));
file_put_contents($cwd . DIRECTORY_SEPARATOR . 'font.z', '');
exit(0);
PHP
        );

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_emptyz_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new PzPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessage('font.z esperado');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function testMakefontProcessFailureThrowsWithExitCode(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $script = tempnam(sys_get_temp_dir(), 'pzpdf_fake_mf_');
        self::assertNotFalse($script);
        $scriptPhp = $script.'.php';
        self::assertTrue(rename($script, $scriptPhp));
        file_put_contents($scriptPhp, "<?php\nfwrite(STDERR, 'simulated failure');\nexit(3);\n");

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_mkfont_fail_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $resolver = new PzPdfTtfFontResolver($scriptPhp);

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessageMatches('/makefont falhou/');

            $resolver->ensureFpdfFontFiles($font, $cache);
        } finally {
            unlink($scriptPhp);
            $this->deleteTree($cache);
        }
    }

    public function testRebuildsWhenCompleteMarkerJsonIsInvalid(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_bad_json_marker_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'.complete', '{invalid json');

        try {
            $resolver = new PzPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertInstanceOf(PzPdfResolvedTtfFont::class, $resolved);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function testRebuildsWhenCompleteMarkerVersionIsStale(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_stale_marker_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents(
            $entry.DIRECTORY_SEPARATOR.'.complete',
            json_encode(['v' => 0, 'sha256' => $hash], JSON_THROW_ON_ERROR),
        );
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.php', str_repeat('<?php //', 40));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.z', 'z');

        try {
            $resolver = new PzPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertSame('font.php', $resolved->definitionFileName);
            self::assertFileExists($entry.DIRECTORY_SEPARATOR.'font.z');
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function testRebuildsWhenCompleteMarkerSha256Mismatch(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente.');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_sha_mismatch_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        $hash = hash_file('sha256', $font);
        self::assertNotFalse($hash);
        $entry = $cache.DIRECTORY_SEPARATOR.$hash;
        self::assertTrue(mkdir($entry, 0777, true));
        file_put_contents(
            $entry.DIRECTORY_SEPARATOR.'.complete',
            json_encode(['v' => 1, 'sha256' => str_repeat('0', 64)], JSON_THROW_ON_ERROR),
        );
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.php', str_repeat('<?php //', 40));
        file_put_contents($entry.DIRECTORY_SEPARATOR.'font.z', 'zdata');

        try {
            $resolver = new PzPdfTtfFontResolver;
            $resolved = $resolver->ensureFpdfFontFiles($font, $cache);
            self::assertInstanceOf(PzPdfResolvedTtfFont::class, $resolved);
        } finally {
            $this->deleteTree($cache);
        }
    }

    public function testInstanceCacheReturnsSameObjectForRepeatedCalls(): void
    {
        $projectRoot = dirname(__DIR__, 5);
        $font = $projectRoot.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.'DejaVuSans.ttf';
        if (! is_file($font)) {
            self::markTestSkipped('Fixture DejaVuSans.ttf ausente (copie tests/Fixtures/fonts).');
        }

        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_unit_ttf_cache_'.uniqid('', true);
        self::assertTrue(mkdir($cache, 0777, true));

        try {
            $resolver = new PzPdfTtfFontResolver;
            $a = $resolver->ensureFpdfFontFiles($font, $cache);
            $b = $resolver->ensureFpdfFontFiles($font, $cache);

            self::assertInstanceOf(PzPdfResolvedTtfFont::class, $a);
            self::assertSame($a, $b);
        } finally {
            $this->deleteTree($cache);
        }
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
