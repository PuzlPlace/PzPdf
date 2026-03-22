<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Tooling;

use PHPUnit\Framework\TestCase;

final class PzPdfNamingConventionTest extends TestCase
{
    public function testPublicSurfaceUnderSrcFollowsPzPdfPrefix(): void
    {
        $root = dirname(__DIR__, 3);
        $src = $root.DIRECTORY_SEPARATOR.'src';

        $violations = PzPdfNamingConventionScanner::scanSourceTree($src);

        self::assertSame(
            [],
            $violations,
            "Violações RF-04 (prefixo PzPdf):\n".implode("\n", $violations)
        );
    }

    public function testScannerFlagsClassWithoutPrefix(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pzpdf_naming_test_'.uniqid('', true);
        self::assertTrue(mkdir($dir, 0777, true));

        try {
            file_put_contents(
                $dir.DIRECTORY_SEPARATOR.'BadActor.php',
                <<<'PHP'
<?php

declare(strict_types=1);

namespace X;

final class NotPzPdfNamed
{
}

PHP
            );

            $violations = PzPdfNamingConventionScanner::scanSourceTree($dir);

            self::assertNotEmpty($violations);
            self::assertStringContainsString('NotPzPdfNamed', $violations[0]);
        } finally {
            $this->deleteTree($dir);
        }
    }

    public function testScannerIgnoresAnonymousClass(): void
    {
        $php = <<<'PHP'
<?php

declare(strict_types=1);

namespace X;

$o = new class {
    public function x(): void {}
};

final class PzPdfOk
{
}

PHP;

        $names = PzPdfNamingConventionScanner::extractTypeNames($php);
        self::assertSame(['PzPdfOk'], $names['class']);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
