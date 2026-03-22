<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\Service\PzPdfTtfFontCacheConfig;

#[CoversClass(PzPdfTtfFontCacheConfig::class)]
final class TtfFontCacheConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearTokenEnvironment();
    }

    protected function tearDown(): void
    {
        putenv('PZPDF_CACHE_DIR');
        putenv('PZPDF_TTF_CACHE');
        $this->clearTokenEnvironment();
        parent::tearDown();
    }

    private function clearTokenEnvironment(): void
    {
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        unset($_ENV['TEST_TOKEN'], $_ENV['UNIQUE_TEST_TOKEN'], $_SERVER['TEST_TOKEN'], $_SERVER['UNIQUE_TEST_TOKEN']);
    }

    public function test_reads_pzpdf_ttf_cache_from_superglobal_when_not_in_process_env(): void
    {
        putenv('PZPDF_CACHE_DIR');
        putenv('PZPDF_TTF_CACHE');
        $legacy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_superglobal_ttf_'.uniqid('', true);
        $_ENV['PZPDF_TTF_CACHE'] = $legacy;

        try {
            $resolved = PzPdfTtfFontCacheConfig::resolve(null);
            self::assertSame($legacy, $resolved);
        } finally {
            unset($_ENV['PZPDF_TTF_CACHE']);
        }
    }

    public function test_legacy_pzpdf_ttf_cache_used_when_pzpdf_cache_dir_unset(): void
    {
        putenv('PZPDF_CACHE_DIR');
        $legacy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_legacy_ttf_'.uniqid('', true);
        putenv('PZPDF_TTF_CACHE='.$legacy);
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');

        $resolved = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertSame($legacy, $resolved);
    }

    public function test_explicit_path_rejects_existing_file(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'pzpdf_cfg_file_');
        self::assertNotFalse($file);

        try {
            $this->expectException(PzPdfException::class);
            $this->expectExceptionMessage('must be a directory');

            PzPdfTtfFontCacheConfig::resolve($file);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_explicit_directory_normalized(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_cfg_dir_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));

        try {
            $resolved = PzPdfTtfFontCacheConfig::resolve($dir.DIRECTORY_SEPARATOR);
            self::assertSame($dir, $resolved);
        } finally {
            rmdir($dir);
        }
    }

    public function test_pzpdf_cache_dir_appends_ttf_fonts_subdirectory(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_env_base_'.uniqid('', true);
        putenv('PZPDF_CACHE_DIR='.$base);
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');

        $resolved = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertStringStartsWith($base, $resolved);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR.'ttf-fonts', $resolved);
    }

    public function test_test_token_appends_isolation_segment(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_env_tok_'.uniqid('', true);
        putenv('PZPDF_CACHE_DIR='.$base);
        putenv('TEST_TOKEN=worker_a');
        putenv('UNIQUE_TEST_TOKEN');

        $resolved = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertStringContainsString('worker_a', $resolved);
        self::assertStringEndsNotWith('worker_a', $base.DIRECTORY_SEPARATOR.'ttf-fonts');
    }

    public function test_unique_test_token_takes_precedence_over_test_token(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_env_utok_'.uniqid('', true);
        putenv('PZPDF_CACHE_DIR='.$base);
        putenv('TEST_TOKEN=ignored');
        putenv('UNIQUE_TEST_TOKEN=proc_99');

        $resolved = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertStringContainsString('proc_99', $resolved);
        self::assertStringNotContainsString('ignored', $resolved);
    }

    public function test_explicit_path_ignores_env_tokens(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_explicit_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));
        putenv('TEST_TOKEN=should_not_appear');

        try {
            $resolved = PzPdfTtfFontCacheConfig::resolve($dir);
            self::assertSame($dir, $resolved);
            self::assertStringNotContainsString('should_not_appear', $resolved);
        } finally {
            rmdir($dir);
            putenv('TEST_TOKEN');
        }
    }
}
