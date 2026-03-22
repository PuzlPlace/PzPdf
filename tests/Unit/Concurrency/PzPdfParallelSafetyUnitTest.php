<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Concurrency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Service\PzPdfTtfFontCacheConfig;

#[CoversClass(PzPdfTtfFontCacheConfig::class)]
final class PzPdfParallelSafetyUnitTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('PZPDF_CACHE_DIR');
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        parent::tearDown();
    }

    public function testUniqueTestTokenTakesPrecedenceOverTestTokenForCachePath(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_unit_tok_'.uniqid('', true);
        putenv('PZPDF_CACHE_DIR='.$base);
        putenv('TEST_TOKEN=ignored_when_unique_set');
        putenv('UNIQUE_TEST_TOKEN=runner_xyz');

        $path = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertStringContainsString('runner_xyz', $path);
        self::assertStringNotContainsString('ignored_when_unique_set', $path);
    }

    public function testTestTokenIsolatedPathsDifferForSameBase(): void
    {
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_unit_tok2_'.uniqid('', true);
        putenv('PZPDF_CACHE_DIR='.$base);

        putenv('TEST_TOKEN=alpha_proc');
        $a = PzPdfTtfFontCacheConfig::resolve(null);

        putenv('TEST_TOKEN=beta_proc');
        $b = PzPdfTtfFontCacheConfig::resolve(null);

        self::assertNotSame($a, $b);
    }
}
