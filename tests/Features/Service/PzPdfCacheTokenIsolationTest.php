<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Service;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Service\PzPdfTtfFontCacheConfig;

#[CoversNothing]
final class PzPdfCacheTokenIsolationTest extends TestCase
{
    public function testDifferentTestTokensYieldDistinctResolvedCacheRoots(): void
    {
        $snapshot = [
            'UNIQUE_TEST_TOKEN' => getenv('UNIQUE_TEST_TOKEN'),
            'TEST_TOKEN' => getenv('TEST_TOKEN'),
            'PZPDF_CACHE_DIR' => getenv('PZPDF_CACHE_DIR'),
        ];

        try {
            // Paratest define UNIQUE_TEST_TOKEN por processo; ele tem precedência sobre TEST_TOKEN
            // na resolução do cache. Limpar aqui garante que o teste controle o segmento via TEST_TOKEN.
            putenv('UNIQUE_TEST_TOKEN=');
            unset($_ENV['UNIQUE_TEST_TOKEN']);

            $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_tok_isolation_'.uniqid('', true);
            putenv('PZPDF_CACHE_DIR='.$base);

            putenv('TEST_TOKEN=suite_alpha');
            $pathA = PzPdfTtfFontCacheConfig::resolve(null);

            putenv('TEST_TOKEN=suite_beta');
            $pathB = PzPdfTtfFontCacheConfig::resolve(null);

            self::assertNotSame($pathA, $pathB);
            self::assertStringContainsString('suite_alpha', $pathA);
            self::assertStringContainsString('suite_beta', $pathB);
        } finally {
            self::restoreEnvVar('UNIQUE_TEST_TOKEN', $snapshot['UNIQUE_TEST_TOKEN']);
            self::restoreEnvVar('TEST_TOKEN', $snapshot['TEST_TOKEN']);
            self::restoreEnvVar('PZPDF_CACHE_DIR', $snapshot['PZPDF_CACHE_DIR']);
        }
    }

    /**
     * @param  string|false  $value  Valor retornado por {@see getenv()} antes do teste
     */
    private static function restoreEnvVar(string $name, string|false $value): void
    {
        if ($value === false || $value === '') {
            putenv($name.'=');
            unset($_ENV[$name]);
        } else {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
        }
    }
}
